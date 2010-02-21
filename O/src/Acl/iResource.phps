<?php

interface O_Acl_iResource {
	public function aclUserCan($action, O_Acl_iUser $user=null);
}