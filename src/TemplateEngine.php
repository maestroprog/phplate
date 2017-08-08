<?php
/**
 * Author: Assasin (iassasin@yandex.ru)
 * License: beerware
 * Use for good
 */

namespace Iassasin\Phplate;

class TemplateEngine {

	private static $instance;

	public $globalVars = [];

	private $tplPath;
	private $options;
	private $userFunctions;
	private $tplCache = [];

	public static function instance(): self {
		return self::$instance ?? self::$instance = new self('./', new TemplateOptions());
	}

	public static function init($tplPath, TemplateOptions $options = null) {
		return self::$instance = new self($tplPath, $options ?: new TemplateOptions());
	}

	private function __construct(string $tplPath, TemplateOptions $options){
		$this->tplPath = $tplPath;
		$this->options = $options;
		$this->userFunctions = new PipeFunctionsContainer($options);
	}

	public function getOptions(): TemplateOptions {
		return $this->options;
	}

	public function getUserFunctions(): PipeFunctionsContainer {
		return $this->userFunctions;
	}

	public function addUserFunctionHandler(string $name, callable $f){
		if ($this->userFunctions->has($name)){
			throw new \RuntimeException("Функция с именем \"{$name}\" уже была добавлена.");
		}
		$this->userFunctions->add($name, $f);
	}

	public function addGlobalVar($name, $val){
		$this->globalVars[$name] = $val;
	}

	/**
	 * Вставляет в шаблон $tplName переменные из массива $values
	 * @param string $tplName - имя шаблона
	 * @param array $values - ассоциативный массив параметров вида ['arg' => 'val'] любой вложенности.
	 * @return string
	 */
	public function build($tplName, array $values): string {
		$p = self::instance()->compile($tplName);
		if (is_string($p)){
			return $p;
		}
		$p->run($values);

		return $p->getResult();
	}

	/**
	 * Вставляет в шаблон $tplStr переменные из массива $values
	 * @param string $tplStr - код шаблона
	 * @param array $values - ассоциативный массив параметров вида ['arg' => 'val'] любой вложенности.
	 * @return string
	 */
	public function buildStr($tplStr, array $values): string {
		$c = new TemplateCompiler($this->options);
		$c->compile($tplStr);
		$p = new Template($c->getProgram());
		$p->run($values);

		return $p->getResult();
	}

	public function compile($tplName){
		$tpath = $this->tplPath . $tplName . '.html';
		$tcpath = $this->tplPath . $tplName . '.ctpl';

		if ($this->options->getCacheEnabled() && file_exists($tcpath)){
			if (!file_exists($tpath) || filemtime($tcpath) >= filemtime($tpath)){
				$pgm = json_decode(file_get_contents($tcpath), true);
				if ($pgm !== false){
					$p = new Template($pgm);
					$this->tplCache[$tpath] = $p;

					return $p;
				}
			}
		}

		if (file_exists($tpath)){
			try{
				$p = null;
				if (array_key_exists($tpath, $this->tplCache)){
					$p = $this->tplCache[$tpath];
				} else {
					$c = new TemplateCompiler($this->options);
					$c->compile(file_get_contents($tpath));

					$pgm = $c->getProgram();
					if ($this->options->getCacheEnabled()){
						file_put_contents($tcpath, json_encode($pgm));
					}

					$p = new Template($pgm);
					$this->tplCache[$tpath] = $p;
				}

				return $p;
			} catch (\Exception $e){
				return 'Error: ' . $tplName . '.html, ' . $e->getMessage();
			}
		}

		return 'Error: template "' . $tplName . '" not found';
	}
}
