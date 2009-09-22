<?php
/**
 * Template abstraction to handle roles.
 *
 * To avoid usage of default dictionary and css file, change $phrases and $roles_css attributes.
 * No more changes is needed to use this.
 *
 * @see O_Acl_Admin_Cmd
 *
 * @author Dmitry Kurinskiy
 */
abstract class O_Acl_Admin_Tpl extends O_Html_Template {
	/**
	 * Query with roles
	 *
	 * @var O_Dao_Query
	 */
	public $roles;
	/**
	 * Array of actions
	 *
	 * @var unknown_type
	 */
	public $actions;
	/**
	 * Role
	 *
	 * @var O_Acl_Role
	 */
	public $role;
	/**
	 * Dictionary of phrases used in template
	 *
	 * @todo integrate with O_Dict
	 * @var array
	 */
	protected $phrases = Array ("allow" => "Allow", "deny" => "Deny", "clear" => "Inherit", 
								"ed_role" => "Editing role", "action" => "Action", 
								"parent" => "Parent role", "no_parent" => "no parent", 
								"submit" => "Save changes", "reset" => "Reset", 
								"success" => "Saved successfully.", 
								"choose_role" => "Choose the role from list", 
								"failure" => "Errors during saving the role.", 
								"add_new" => "Add new role", "sbm_new" => "Add", 
								"set_visitor" => "Set as visitors role");
	/**
	 * Css file source
	 *
	 * @var string
	 */
	protected $roles_css;

	public function displayContents()
	{
		if ($this->role) {
			$this->showRole();
			return;
		}
		if ($this->roles_css !== false) {
			if (!$this->roles_css)
				$this->roles_css = $this->layout()->staticUrl( "css/roles.css", true );
			$this->layout()->addCssSrc( $this->roles_css );
		}
		O_Js_Middleware::getFramework()->addSrc( $this->layout() );
		?>
<table width="100%">
	<tr>
		<td id="roles-list">
		<ul>
<?
		foreach ($this->roles as $role) {
			?><li><a href="javascript:void(0)"
				onclick="<?=O_Js_Middleware::getFramework()->ajaxHtml( "role-edit", O_UrlBuilder::get( O_Registry::get( "app/env/process_url" ) ), array ("mode" => "show", "role" => $role->id) )?>"><?=$role->name?></a></li><?
		}
		?>
</ul>
		<?=$this->phrases[ "add_new" ]?>:
		<form method="post" onsubmit="return this.new_role.value != '';"><input
			type="text" name="new_role" /><input type="submit"
			value="<?=$this->phrases[ "sbm_new" ]?>" /></form>
		</td>
		<td id="role-edit-cell">
		<div id="role-edit">
		<h1><?=$this->phrases[ "choose_role" ]?></h1>
		</div>
		</td>
	</tr>
</table>
<?
	}

	protected function showRole()
	{
		$radio = Array (O_Acl_Action::TYPE_ALLOW => $this->phrases[ "allow" ], 
						O_Acl_Action::TYPE_DENY => $this->phrases[ "deny" ], 
						"clear" => $this->phrases[ "clear" ]);
		?>
<form method="POST" id="role-form"
	action="<?=O_UrlBuilder::get( O_Registry::get( "app/env/process_url" ) )?>">
<fieldset><legend><?=$this->phrases[ "ed_role" ]?>: <?=$this->role->name?></legend>
<table>
	<tr>
		<th class="role-act-sub"><?=$this->phrases[ "action" ]?></th>
		<?
		foreach ($radio as $k => $v) {
			?><th class="role-act-<?=$k?>"><?=$v?></th><?
		}
		?>
	</tr>
	<?
		foreach ($this->actions as $action) {
			
			?>
	<tr>
		<th class="role-act"><?=$action?></th>
		<?
			foreach ($radio as $k => $v) {
				?>
		<td class="role-act-<?=$k?>"><input type="radio"
			name="actions[<?=$action?>]" value="<?=$k?>"
			<?=($this->role->getActionStatus( $action ) == $k ? ' checked="yes"' : "")?> /></td>
		<?
			}
			?>
	</tr>
	<?
		}
		?>
	<tr>
		<th class="role-act-sub"><?=$this->phrases[ "parent" ]?></th>
		<td colspan="3" class="role-act-sub"><select name="parent_role">
			<option value="null">- <?=$this->phrases[ "no_parent" ]?> -</option>
			<?
		foreach ($this->roles as $role)
			if ($role->id != $this->role->id) {
				?>
			<option value="<?=$role->id?>"
				<?=($this->role[ "parent" ] == $role->id ? ' selected="yes"' : "")?>><?=$role->name?></option>
			<?
			}
		?>
		</select></td>
	</tr>
	<tr>
		<td colspan="4" align="center" class="role-act-sub"><input
			type="checkbox" name="set_visitor" value="yes"
			<?=($this->role->visitor_role ? ' checked="yes"' : "")?> /> &ndash; <?=$this->phrases[ "set_visitor" ]?>
		</td>
	</tr>
	<tr>
		<th colspan="4" class="role-act-sub"><input type="submit"
			value="<?=htmlspecialchars( $this->phrases[ "submit" ] )?>" /> <input
			type="Reset"
			value="<?=htmlspecialchars( $this->phrases[ "reset" ] )?>" /></th>
	</tr>
</table>
<input type="hidden" name="role" value="<?=$this->role->id?>" /></fieldset>
</form>
<? //TODO: make this js-framework-undependent
		?>
<script type="text/javascript">
 $('role-form').getElement('input[type=submit]').addEvent("click", function(e){
	 e.stop();
 	$(this).disabled = true;
 	new Request.JSON({url:$('role-form').getAttribute('action'), onSuccess:function(response){
		if(response.status) {
			alert("<?=htmlspecialchars( $this->phrases[ "success" ] )?>");
			$('role-form').getElement('input[type=submit]').disabled = false;
		} else {
			alert("<?=htmlspecialchars( $this->phrases[ "failure" ] )?>");
		}
 	 }}).post($('role-form'));
 });
 </script>
<?
	}

}