<?php

namespace Magice\Bundle\RestBundle\Controller;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use FOS\RestBundle\Controller\FOSRestController;
use Magice\Bundle\RestBundle\Security\ExpressionLanguage;
use Magice\Bundle\RestBundle\Util\FormProcessing;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class RestController extends FOSRestController
{
    /**
     * @return \Symfony\Component\Security\Core\User\UserInterface;
     */
    public function getUser()
    {
        return parent::getUser();
    }

    /**
     * @param object $object
     * @param string $propertyName
     *
     * @return bool
     */
    private function hasObjectProperty($object, $propertyName)
    {
        $class = new \ReflectionClass($object);

        return $class->hasProperty($propertyName);
    }

    protected function dataTransform($object, $field)
    {
        if (!$this->hasObjectProperty($object, $field)) {
            return null;
        }

        $getter = 'get' . $field;
        $value = $object->$getter();

        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d H:i:s');
        }

        // TODO: check instanceof other (depen on Doctrine DBAL Type)

        if (is_object($value)) {
            if (method_exists($value, 'getId')) {
                $value = $value->getId();
            } else {
                throw new BadRequestHttpException(sprintf("Unsuport '%s' as format '%s'.", $field, get_class($value)));
            }
        }

        return $value;
    }

    /**
     * @param FormTypeInterface $form
     * @param object $classData
     * @param FormProcessing $processing
     *
     * @return \Symfony\Component\Form\Form|FormTypeInterface
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    protected function processForm(FormTypeInterface $form, $classData, FormProcessing $processing = null)
    {
        $request = $this->get('request');
        $form = $this->createForm($form, $classData);
        $processing = $processing ?: new FormProcessing();

        $processing->init($request, $form, $classData);

        if ($request->getContentType() === 'json') {
            $content = $request->getContent();
            $data = array();

            $submitData = json_decode($content, true);

            // if submit with form name
            if (array_key_exists($form->getName(), $submitData)) {
                $submitData = $submitData[$form->getName()];
            }

            /**
             * simulate form submit data
             * @var FormInterface $child
             */
            foreach ($form as $child) {
                $name = $child->getName();

                // get data from client submit
                if (array_key_exists($name, $submitData)) {
                    $value = $submitData[$name];

                    if (is_array($value) && array_key_exists('id', $value)) {
                        $value = $value['id'];
                    }

                    $data[$name] = $value;

                } else {
                    // set origin data to default if not submit
                    // this must use in form validation
                    $data[$name] = $this->dataTransform($classData, $name);
                }

                // final process by defined client class
                $data[$name] = $processing->process($name, $data);
            }

            if ($request->headers->has('X-Requested-With-Sencha')) {
                // TODO: if any think to handle
            }

            $data = $processing->beforeSubmit($data);

            if (empty($data)) {
                throw new BadRequestHttpException("No data to submit to form.");
            }

            $form->submit($data);
        } else {
            $form->handleRequest($request);
        }

        return $form;
    }

    /**
     * @param string $name
     *
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * @param null $name
     * @return \Magice\Bundle\RestBundle\Domain\Manager
     */
    protected function getDomainManager($name = null)
    {
        $dm = $this->get('mg.rest.manager');

        if ($name) {
            $dm->setManager($this->getEntityManager($name));
        }

        return $dm;
    }

    /**
     * @param object $entity
     * @param \Doctrine\Common\Persistence\ObjectManager|string $manager
     *
     * @return object
     * @deprecated use domain manager($dm = $this->getDomainManager())
     */
    protected function save($entity, $manager = null)
    {
        $em = (is_string($manager) ? $this->getEntityManager($manager) : $manager) ?: $this->getEntityManager();
        $em->persist($entity);
        $em->flush($entity);

        return $entity;
    }

    /**
     * @param object $entity
     * @param \Doctrine\Common\Persistence\ObjectManager|string $manager
     * @deprecated use domain manager($dm = $this->getDomainManager())
     */
    protected function delete($entity, $manager = null)
    {
        $em = (is_string($manager) ? $this->getEntityManager($manager) : $manager) ?: $this->getEntityManager();
        $em->remove($entity);
        $em->flush($entity);
    }

    /**
     * @param string|object $target #Service #Entity
     * @param int $id
     * @param string|null $manager
     *
     * @return object
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @deprecated use domain manager($dm = $this->getDomainManager())
     */
    protected function findNotFound($target, $id, $manager = null)
    {
        $em = $this->getEntityManager($manager);

        if (is_object($target)) {
            if ($target instanceof ObjectRepository) {
                // repository service
                $entity = $target->find($id);
            } else {
                // entity object
                $entity = $em->find(get_class($target), $id);
            }
        } else {
            if ($this->container->has($target) && ($target = $this->get(
                    $target
                )) && $target instanceof ObjectRepository
            ) {
                // repository service id
                $entity = $target->find($id);
            } else {
                // entity class name
                $entity = $em->find($target, $id);
            }
        }

        if (empty($entity)) {
            throw new NotFoundHttpException(sprintf("Not found entity resource with id (%s).", $id));
        }

        return $entity;
    }

    /**
     * @param string|object $target #Service #Entity
     * @param int $id
     * @param string|null $manager
     *
     * @return object
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @deprecated use domain manager($dm = $this->getDomainManager())
     */
    protected function en($target, $id, $manager = null)
    {
        return $this->findNotFound($target, $id, $manager);
    }

    /**
     * @param string $expression eg. ->acl("is_granted('OWNER')", $entity)
     * @param null $object
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function acl($expression, $object = null)
    {
        $roleHierarchy = $this->get('security.role_hierarchy');
        $trustResolver = $this->get('security.authentication.trust_resolver');
        $securityContext = $this->get('security.context');
        $request = $this->get('request');
        $token = $securityContext->getToken();

        if (null !== $roleHierarchy) {
            $roles = $roleHierarchy->getReachableRoles($token->getRoles());
        } else {
            $roles = $token->getRoles();
        }

        $variables = array(
            'token' => $token,
            'user' => $token->getUser(),
            'object' => $object,
            'request' => $request,
            'roles' => array_map(
                function ($role) {
                    return $role->getRole();
                },
                $roles
            ),
            'trust_resolver' => $trustResolver,
            'security_context' => $securityContext,
        );

        // controller variables should also be accessible
        $values = array_merge($request->attributes->all(), $variables);

        if (!ExpressionLanguage::evaluation($expression, $values)) {
            throw new AccessDeniedException();
        }
    }
}
