<?php
namespace Magice\Bundle\RestBundle\Domain;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class DomainManager
 */
class Manager
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var object
     */
    private $resource;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ObjectManager $manager, ValidatorInterface $validator, ContainerInterface $container)
    {
        $this->validator = $validator;
        $this->manager = $manager;
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     * @return $this
     */
    public function setManager(ObjectManager $manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @param null $resource
     */
    public function save($resource = null)
    {
        $this->resource = $resource ?: $this->resource;
        $this->manager->persist($this->resource);
        $this->manager->flush();
    }

    /**
     * @param null $resource
     */
    public function delete($resource = null)
    {
        $this->resource = $resource ?: $this->resource;
        $this->manager->remove($resource);
        $this->manager->flush();
    }

    /**
     * @param $resource
     * @param array $validationGroups
     * @return $this
     */
    public function validate($resource, $validationGroups = array())
    {
        $this->resource = $resource;
        $violations = $this->validator->validate($this->resource, null, $validationGroups);

        if (count($violations)) {
            $exception = new ManagerException(
                sprintf('Data for resource (%s) constraint violation.', get_class($this->resource))
            );

            $exception->setViolations($violations);

            throw $exception;
        }

        return $this;
    }

    /**
     * @param $resource
     * @param array $validationGroups
     * @return bool
     */
    public function isValid($resource, $validationGroups = array())
    {
        try {
            $this->validate($resource, $validationGroups);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $resource
     * @param $data
     * @return $this
     */
    public function apply($resource, $data)
    {
        $this->resource = $resource;

        $me = new \ReflectionClass($this->resource);

        if (is_object($data)) {
            $cls = new \ReflectionClass($data);
            foreach ($cls->getProperties() as $property) {
                $name = $property->getName();

                if ($me->hasProperty($name)) {

                    $setter = 'set' . $name;
                    $getter = 'get' . $name;

                    if ($property->isPublic()) {
                        $this->$setter($data->$name);
                    } elseif ($cls->hasMethod($getter)) {
                        $this->$setter($data->$getter());
                    } else {
                        // cannot get value
                        continue;
                    }
                }
            }
        }

        if (is_array($data)) {
            foreach ($data as $name => $value) {
                if ($me->hasProperty($name)) {
                    $setter = 'set' . $name;
                    $this->$setter($value);
                }
            }
        }

        return $this;
    }

    /**
     * @param $resource
     * @param $idOrCriterias
     * @return mixed
     */
    protected function findNotFound($resource, $idOrCriterias)
    {
        $methodFind = is_array($idOrCriterias) ? 'findOneBy' : 'find';

        if (is_object($resource)) {
            if ($resource instanceof ObjectRepository) {
                // repository service
                $resource = $resource->$methodFind($idOrCriterias);
            } else {
                // entity object
                $resource = $this->manager->getRepository(get_class($resource))->$methodFind($idOrCriterias);
            }
        } else {
            if ($this->container->has($resource)
                && ($repository = $this->container->get($resource))
                && $repository instanceof ObjectRepository
            ) {
                // repository service id
                $resource = $repository->$methodFind($idOrCriterias);
            } else {
                // entity class name
                $resource = $this->manager->getRepository($resource)->$methodFind($idOrCriterias);
            }
        }

        if (empty($resource)) {
            throw new NotFoundHttpException(
                sprintf("Not found resource by criterias (%s).", implode(',', (array)$idOrCriterias))
            );
        }

        return $resource;
    }
}