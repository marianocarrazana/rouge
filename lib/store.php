<?php
namespace Rouge;

/**
 * Global store shared between the server and client
 * 
 * The global store, accesible from the server(PHP) with templates variables 
 * and from the client(javascript) with OnePage.store object.
 */
class Store
{
	private $variables = [];
	private $serverVariables = [];
	private $clientVariables = [];
	
	/**
	 * This class is autoloaded by the Loader class
	 * 
	 * @param array $globalVariables Initial global variables 
	 */
	public function __construct(array $globalVariables = [])
	{
		$this->variables = $globalVariables;
	}

	/**
	 * Add a global variable shared between server and client
	 * 
	 * @param string $name the name accesible in php and js
	 * @param $value The content
	 */
	public function addVariable(string $name, $value){
		$this->variables[$name] = $value;
	}

	public function addClientVariable(string $name, $value){
		$this->clientVariables[$name] = $value;
	}

	public function addServerVariable(string $name, $value){
		$this->serverVariables[$name] = $value;
	}

	public function setServerVariables(array $variables){
		$this->serverVariables = $variables;
	}

	public function setVariables(array $variables){
		$this->variables = $variables;
	}

	public function setClientVariables(array $variables){
		$this->clientVariables = $variables;
	}

	public function getServerVariables(){
		return array_merge($this->variables,$this->serverVariables);
	}

	public function getClientVariables(){
		return array_merge($this->variables,$this->clientVariables);
	}


}