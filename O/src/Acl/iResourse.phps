<?php

interface O_Acl_iResourse {
	public function aclUserCan($action, O_Acl_iUser $user=null);
}