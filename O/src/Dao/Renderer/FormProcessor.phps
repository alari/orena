<?php
/**
 * Table:
 * -edit:title
 * -edit:create-title
 * -edit:submit
 * -edit:create-submit
 * -edit:reset
 * -edit:create-reset
 *
 * Field:
 * -edit callback
 * -check callback
 * -check:before
 * -required error-string
 * -edit:title title
 * -title title
 *
 */
class O_Dao_Renderer_FormProcessor extends O_Dao_Renderer_FormBases {
	
	/**
	 * Instances counter -- to give an unique id to each form
	 *
	 * @var int
	 */
	private static $instancesCount = 0;

	/**
	 * Simple constructor
	 *
	 */
	public function __construct()
	{
		$this->actionUrl = O_UrlBuilder::get( O_Registry::get( "app/env/process_url" ) );
		$this->instanceId = "oo-form-" . (++self::$instancesCount);
	}

	/**
	 * Tries to handle the form, returns true on success
	 *
	 * @return bool
	 */
	public function handle()
	{
		// handle only once
		if ($this->handled)
			return $this->handleResult;
		$this->handled = true;
		
		if (!$this->isFormRequest())
			return $this->handleResult = false;
			
		// Load record, if needed
		if (!$this->record && $this->createMode === 0) {
			$this->record = O_Dao_ActiveRecord::getById( O_Registry::get( "app/env/params/id" ), $this->class );
			if (!$this->record) {
				$this->errors[ "_" ] = "Record not found.";
				return $this->handleResult = false;
			}
		}
		
		// Check and prepare values, found errors if they are
		$this->checkValues();
		
		// Stop processing without saving, if errors occured
		if (count( $this->errors )) {
			return $this->handleResult = false;
		}
		
		// Create record in database
		if ($this->createMode !== 0 && !$this->record) {
			$class = $this->class;
			if (count( $this->createMode )) {
				$refl = new ReflectionClass( $class );
				$this->record = $refl->newInstanceArgs( $this->createMode );
			} else {
				$this->record = new $class( );
			}
		}
		
		// Setting values for ActiveRecord
		foreach ($this->values as $name => $value) {
			$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $name );
			// Simple assigning
			if ($fieldInfo->isAtomic() || $fieldInfo->isRelationOne()) {
				$this->record->$name = $value;
				// Removing old values, assigning new ones
			} elseif ($fieldInfo->isRelationMany()) {
				$field = $this->record->$name;
				if (is_array( $value )) {
					foreach ($field as $id => $obj) {
						if (!isset( $value[ $id ] ))
							$field->remove( $obj, $fieldInfo->isRelationOwns() );
					}
					foreach ($value as $id => $obj) {
						if (!isset( $field[ $id ] ))
							$field[] = $obj;
					}
				} else {
					$field->removeAll( $fieldInfo->isRelationOwns() );
				}
			}
		}
		
		// Trying to save
		try {
			$this->record->save();
		}
		catch (PDOException $e) {
			$this->errors[ "_" ] = "Duplicate entries found. Saving failed.";
			return $this->handleResult = 0;
		}
		
		// Succeed
		return $this->handleResult = 1;
	}

	/**
	 * Print response to be handled as an ajax response
	 *
	 * @param mixed $refreshOrLocation if 1 or true, refresh, else -- it's an url
	 * @param string $showOnSuccess if not set and there's no value for refreshing, this will be shown in response; otherwise object will be shown
	 * @return bool true if response was echoed, false if form wasn't handled
	 */
	public function responseAjax( $refreshOrLocation = null, $showOnSuccess = null )
	{
		if (!$this->isFormRequest())
			return false;
		$response = Array ("status" => "");
		if ($this->handle()) {
			$response[ "status" ] = "SUCCEED";
			
			if ($refreshOrLocation === 1 || $refreshOrLocation === true) {
				$response[ "refresh" ] = 1;
			} elseif ($refreshOrLocation) {
				$response[ "redirect" ] = $refreshOrLocation;
			
			} elseif (!$showOnSuccess) {
				ob_start();
				$this->record->show( $this->layout, $this->showType );
				$response[ "show" ] = ob_get_clean();
			} else {
				$response[ "show" ] = $showOnSuccess;
			}
		} else {
			$response[ "status" ] = "FAILED";
			$response[ "errors" ] = $this->errors;
		}
		echo json_encode( $response );
		return true;
	}

}