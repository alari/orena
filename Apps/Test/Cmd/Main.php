<?php

class Test_Cmd_Main extends O_OpenId_Consumer_Command {

	protected function authSuccess( Auth_OpenID_ConsumerResponse $response )
	{
		$tpl = $this->getTemplate();
		$tpl->error = $response->getDisplayIdentifier()." is OKAY";
		return $tpl;
	}
}