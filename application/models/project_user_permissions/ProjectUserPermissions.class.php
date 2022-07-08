<?php
/**
* ProjectUserPermissions
* @author Brett Edgar (TheWalrus) True Digital Security, Inc. www.truedigitalsecurity.com
* @http://www.projectpier.org/
*/
class ProjectUserPermissions extends BaseProjectUserPermissions {

  private $permissions_cache = array();

  static function getPermissionsForProjectUser(ProjectUser $project_user) {
    $permissions = array();
    $pups = ProjectUserPermissions::instance()->findAll(
      array(
       'conditions' => '`project_id` = '.$project_user->getProjectId().' and `user_id` = '.$project_user->getUserId()
      )
    );
    if (is_array($pups)) {
      foreach ($pups as $pup) {
        $permissions[] = Permissions::getPermissionString($pup->getPermissionId());
      }
    } //if
    return $permissions;
  } //getPermissionsForProjectUser
} // ProjectUserPermissions
?>