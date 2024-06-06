<?php

namespace App\Helpers\Migration\Creators;

use App\Helpers\Migration\Creators\Entities\Entity;
use App\Http\Models\Accounts;
use App\Http\Models\Orm;
use App\Http\Models\UsersRoles;
use Illuminate\Support\Facades\DB;

class TriggersCreator extends EntityCreator
{
    const ENTITIES_DIR = '/triggers';
    const ENTITY_TYPE = 'TRIGGER';

    protected function getTypeStyles(): string
    {
        return 'fg=green';
    }

    protected function getEntitiesQuery(): string
    {
        return  "SHOW TRIGGERS";
    }

    protected function setEntitiesKeys(): callable
    {
        return function ($item) {
            return strtolower($item->Trigger);
        };
    }

    public function getVariables(Entity $entity): array
    {
        $userOnlyReadRoleId = UsersRoles::USER_ONLY_READER_ROLE_ID;
        $userRoleId = UsersRoles::USER_ROLE_ID;
        $adminRoleId = UsersRoles::ADMIN_ROLE_ID;
        $accountSuspendedStatus = Accounts::ACCOUNT_STATUS_SUSPENDED;
        $mysqlCustomCodePermissionsDenied = Orm::MYSQL_CUSTOM_CODE_PERMISSIONS_DENIED;
        $mysqlCustomCodeDeleteEntityDataTableDenied = Orm::MYSQL_CUSTOM_CODE_DELETE_ENTITY_DATA_TABLE_DENIED;

        $errorMessage = UsersRoles::NOT_PERMISSION_MESSAGE;
        $restrictedAdminRoleId = UsersRoles::RESTRICTED_ADMIN_ROLE_ID;
        $variables = config('migrations');

        $variables['checkUserRole'] = <<<SQL
IF (
    IFNULL(@auth_user_role, '') = '$userOnlyReadRoleId' OR 
    (IFNULL(@auth_user_role, '') = '$userRoleId' AND IFNULL(@auth_account_status, '') = '$accountSuspendedStatus') OR 
    (IFNULL(@auth_user_role, '') = '$restrictedAdminRoleId' AND @doNotCheckRestrictedAdmin IS NULL AND @auth_account_id IS NOT NULL AND !EXISTS(SELECT 1 FROM user_accounts WHERE account_id = @auth_account_id AND user_id = @auth_user_id))
) THEN
    SIGNAL SQLSTATE '$mysqlCustomCodePermissionsDenied' SET MESSAGE_TEXT = '$errorMessage';
END IF;
SQL;

        $variables['allowActionOnlyByAdmin'] = <<<SQL
    IF (@auth_user_id IS NOT NULL AND @auth_user_role IS NOT NULL AND @auth_user_role <> '$adminRoleId') THEN
        SIGNAL SQLSTATE '$mysqlCustomCodePermissionsDenied' SET MESSAGE_TEXT = 'Only admin has permission to do that';
    END IF;
SQL;

        $variables['checkUserRoleIfAccountAndUserUsed'] = <<<SQL
IF (
    IFNULL(@auth_user_role, '') = '$userOnlyReadRoleId' OR 
    (IFNULL(@auth_user_role, '') = '$userRoleId' AND IFNULL(@auth_account_status, '') = '$accountSuspendedStatus') OR 
    (IFNULL(@auth_user_role, '') = '$restrictedAdminRoleId' AND (NEW.account_id != @auth_account_id OR NEW.user_id != @auth_user_id))
) THEN
    SIGNAL SQLSTATE '$mysqlCustomCodePermissionsDenied' SET MESSAGE_TEXT = '$errorMessage';
END IF;
SQL;

        $variables['doNotAllowActionByRestrictedAdmin'] = <<<SQL
    IF (@auth_user_id IS NOT NULL AND @auth_user_role IS NOT NULL AND @auth_user_role = '$restrictedAdminRoleId') THEN
        SIGNAL SQLSTATE '$mysqlCustomCodePermissionsDenied' SET MESSAGE_TEXT = 'User with RestrictedAdmin role is not allowed to do this action';
    END IF;
SQL;

        $variables['checkAccountsChangesByRestrictedAdmin'] = <<<SQL
    IF (@auth_user_id IS NOT NULL AND @auth_user_role IS NOT NULL AND @auth_user_role = '$restrictedAdminRoleId') THEN
        IF (!EXISTS(SELECT 1 FROM user_accounts WHERE account_id = OLD.id AND user_id = @auth_user_id)) THEN
            SIGNAL SQLSTATE '$mysqlCustomCodePermissionsDenied' SET MESSAGE_TEXT = 'User with RestrictedAdmin role allowed to do changes only to related accounts';
        END IF;
    END IF;
SQL;

        $variables['checkUsersChangesByRestrictedAdmin'] = <<<SQL
    IF (@auth_user_id IS NOT NULL AND @auth_user_role IS NOT NULL AND @auth_user_role = '$restrictedAdminRoleId' AND @auth_user_id != OLD.id) THEN
        SIGNAL SQLSTATE '$mysqlCustomCodePermissionsDenied' SET MESSAGE_TEXT = 'User with RestrictedAdmin role allowed to do changes only to own user';
    END IF;
SQL;

        $variables['checkChangesByRestrictedAdminByAccountIDField'] = <<<SQL
    IF (@auth_user_id IS NOT NULL AND @auth_user_role IS NOT NULL AND @auth_user_role = '$restrictedAdminRoleId') THEN
        IF (!EXISTS(SELECT 1 FROM user_accounts WHERE account_id = OLD.account_id AND user_id = @auth_user_id)) THEN
            SIGNAL SQLSTATE '$mysqlCustomCodePermissionsDenied' SET MESSAGE_TEXT = 'User with RestrictedAdmin role allowed to do changes only to related accounts';
        END IF;
    END IF;
SQL;

        $variables['checkChangesDeleteFromEntityDataTables'] = <<<SQL
    IF (@triggerOriginator IS NULL) THEN
            SIGNAL SQLSTATE '$mysqlCustomCodeDeleteEntityDataTableDenied' SET MESSAGE_TEXT = 'Delete from Entity Data Table is allowed';
    END IF;
SQL;


        if ($entity->getName() == 'products_after_update_trigger') {
            $productFields = DB::getSchemaBuilder()->getColumnListing('products');
            $isProductDifferentFields = '';
            foreach ($productFields as $field) {
                if (in_array($field, ['id', 'description'])) {
                    continue;
                }
                $isProductDifferentFields .= '
IF (IS_DIFFERENT(NEW.' . $field . ', OLD.' . $field . ')) THEN
    SET _DIFFERENT_FIELDS = JSON_SET(_DIFFERENT_FIELDS, \'$.' . $field . '\', JSON_OBJECT(\'new\', NEW.' . $field . ', \'old\', OLD.' . $field . '));
END IF;
';
            }
            $variables['isProductDifferentFields'] = $isProductDifferentFields;
        }

        if (strpos($entity->getName(), 'before_delete') !== false) {
            $variables['checkUserRoleIfAccountAndUserUsed'] = str_replace("NEW.", "OLD.", $variables['checkUserRoleIfAccountAndUserUsed']);
        }

        if (strpos($entity->getName(), 'before_insert') !== false) {
            $variables['checkChangesByRestrictedAdminByAccountIDField'] = str_replace("OLD.", "NEW.", $variables['checkChangesByRestrictedAdminByAccountIDField']);
        }

        return $variables;
    }

    public function dropIfExists(string $filePath): void
    {
        $separatedPath = explode('/', trim($filePath, '/'));
        $fileName = end($separatedPath);
        $entityName = str_replace('.sql', '', $fileName);
        dump('Dropping trigger: ' . $entityName);
        DB::unprepared('DROP TRIGGER IF EXISTS ' . $entityName);
    }

}
