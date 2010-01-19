<?php

abstract class O_OpenId_Consumer_Template extends O_Html_Template {

	const MODE_AUTH = "auth";
	const MODE_OUR_USER = "our";
	const MODE_EX_CANCEL = "ex_cancel";
	const MODE_EX_INVALID = "ex_invalid";

	public $mode;
	public $error;
	public $identity;


	public function displayContents()
	{

		if ($this->error)
			echo "<h1>", $this->error, "</h1>";

		switch ($this->mode) {
			case self::MODE_AUTH :
			case self::MODE_OUR_USER :
				?>
<form method="post" id="openid-login-form"
	action="<?=
				O_Registry::get( "env/request_url" )?>"><label><span>OpenId:</span> <input
	type="text" name="openid_identifier" value="<?=
				$this->identity?>" /></label>

	<?
				if ($this->mode == "our") {
					?>
	<br />
<label><span>Password:</span> <input type="password" name="pwd" /></label>
<label><input type="submit" value="Sign Up" /></label>
<?
				} else {
					?>
<input type="submit" value="Sign Up" />
<?
				}
				?>

	 <input type="hidden" name="openid_action" value="login" /></form>
<?
			break;
		}

	}
}