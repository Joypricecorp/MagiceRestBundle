<?php
namespace Magice\Bundle\RestBundle\Domain;

use Doctrine\ORM\EntityManager;
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
     * @var EntityManager
     */
    private $manager;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(EntityManager $manager, ValidatorInterface $validator, ContainerInterface $container)
    {
        $this->validator = $validator;
        $this->manager = $manager;
        $this->container = $container;
    }

    /**
     * @param EntityManager $manager
     * @return $this
     */
    public function setManager(EntityManager $manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * set resource
     * @param object $resource
     * @return $this
     */
    public function resource($resource)
    {
        $this->resource = $resource;

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
        $this->manager->remove($this->resource);
        $this->manager->flush();
    }

    /**
     * @param array|object|null $resource Resource to handle (if array mean that is $validationGroups)
     * @param array|null $validationGroups
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function validate($resource = null, $validationGroups = null)
    {
        if (is_array($resource)) {
            $validationGroups = $resource;
        } else {
            $this->resource = $resource ?: $this->resource;
        }

        if (empty($this->resource)) {
            throw new \InvalidArgumentException('Resource can not be empty.');
        }

        $violations = $this->validator->validate($this->resource, $validationGroups);

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
    public function findNotFound($resource, $idOrCriterias)
    {
        $isCompositKeys = is_array($idOrCriterias);
        $idOrCriterias = is_object($idOrCriterias) ? (array) $idOrCriterias : $idOrCriterias;
        $methodFind = is_array($idOrCriterias) && !$isCompositKeys ? 'findOneBy' : 'find';

        if (is_object($resource)) {
            if ($resource instanceof ObjectRepository) {
                // repository service
                $repository = $resource;
            } else {
                // entity object
                $repository = $this->manager->getRepository(get_class($resource));
            }
        } else {
            if ($this->container->has($resource)
                && ($repository = $this->container->get($resource))
                && $repository instanceof ObjectRepository
            ) {
                // repository service id
                // we got it!
            } else {
                // entity class name
                $repository = $this->manager->getRepository($resource);
            }
        }

        $resource = $repository->$methodFind($idOrCriterias);

        if (empty($resource)) {
            throw new NotFoundHttpException(
                sprintf("Not found resource by criterias (%s).", implode(',', (array)$idOrCriterias))
            );
        }

        return $resource;
    }

    /**
     * Begin transaction
     * @param null $resource
     * @return $this
     */
    public function begin($resource = null)
    {
        $this->resource = $resource ?: $this->resource;
        $this->manager->getConnection()->beginTransaction();

        return $this;
    }

    public function commit()
    {
        $this->manager->getConnection()->commit();
    }

    public function rollback()
    {
        $this->manager->getConnection()->rollback();
    }

    public function transactional($func)
    {
        $this->manager->transactional($func);
    }
}
