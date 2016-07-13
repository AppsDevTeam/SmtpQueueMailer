<?php

namespace ADT\SmtpQueueMailer\DI;

use Nette;

/**
 */
class SmtpQueueMailerExtension extends Nette\DI\CompilerExtension {

	/**
	 * @var array
	 */
	public $defaults = [
		'path' => "path",
		'options' => [],
	];

	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		// registrace service
		$builder->addDefinition($this->prefix('service'))
			->setClass('ADT\SmtpQueueMailer\Service\MessageQueue')
			->addSetup("setOptions", [$config])
			->setInject(FALSE);

		// registrace commandu
		$builder->addDefinition($this->prefix('command'))
			->setClass('ADT\SmtpQueueMailer\Console\MailerCommand')
			->setInject(FALSE)
			->addTag('kdyby.console.command');
	}

	public function beforeCompile()
	{
    $builder = $this->getContainerBuilder();

		foreach ($builder->findByTag("queueMailer") as $name => $val) {
			$builder->getDefinition($name)
				->addSetup("injectQueueService", [$this->prefix('@service')]);
		}
	}

	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator) {
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('queueMailer', new SmtpQueueMailerExtension);
		};
	}

}
