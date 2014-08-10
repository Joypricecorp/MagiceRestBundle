<?php
namespace Magice\Bundle\RestBundle\Annotation;

/**
 * @Annotation
 */
class Acl extends Security
{
    /**
     * Example: @Acl({"OWNER,USER,ADMIN", "object"})
     * Example: @Acl({"OWNER USER ADMIN", "object"})
     */
    public function setValue($value)
    {
        $acls   = $value[0];
        $object = empty($value[1]) ? null : $value[1];

        if (!is_array($acls)) {
            if (strpos($acls, ',') !== false) {
                $acls = explode(',', $acls);
            } else {
                $acls = explode(' ', $acls);
            }
        }

        parent::setValue(sprintf("is_granted(%s, %s)", json_encode($acls), $object));
    }
}