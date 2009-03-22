<?php
class O_Dao_Renderer_Check_Params extends O_Dao_Renderer_Params {
	protected $newValue;

	/**
	 * Sets reference of the new value
	 *
	 * @param mixed $newValue
	 */
	public function setNewValueRef( &$newValue )
	{
		$this->newValue = & $newValue;
	}

	/**
	 * Returns reference to new value
	 *
	 * @return mixed
	 */
	public function &newValue()
	{
		return $this->newValue;
	}

	/**
	 * Sets the new value to reference
	 *
	 * @param mixed $newValue
	 */
	public function setNewValue( $newValue )
	{
		$this->newValue = $newValue;
	}
}