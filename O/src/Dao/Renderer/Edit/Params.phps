<?php
class O_Dao_Renderer_Edit_Params extends O_Dao_Renderer_Params {
	protected $title;
	protected $error;

	public function setTitle( $title )
	{
		$this->title = $title;
	}

	public function title()
	{
		return $this->title;
	}

	public function setError( $error )
	{
		$this->error = $error;
	}

	public function error()
	{
		return $this->error;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $rowClass
	 * @return O_Form_Row_Field
	 */
	public function getFormRowField($rowClass) {
		return new $rowClass($this->fieldName, $this->title==1?"":$this->title, $this->params, $this->value, $this->error);
	}


}