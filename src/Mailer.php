<?php

namespace ADT\SmtpQueueMailer;

use Nette;

class Mailer extends \Nette\Mail\SmtpMailer {

	const DB_TABLE = "messages";

	/** @var string */
	protected $options;

	/** @var \Nette\Database\Connection */
	protected $connection;

	/**
	 * @param array $options
	 */
	public function __construct(array $options = array()) {
		$this->options = $options;

		if (empty($options["sqlite"]["path"])) {
			throw new \Nette\InvalidArgumentException("Chybí povinný parametr 'path' pro SQLite databázi");
		}

		parent::__construct($options);
	}

	/**
	 * @param Nette\Mail\Message $mail
	 * @param integer|NULL $messageId ID zprávy z databáze (pokud se jedná o zprávu z DB)
	 */
	public function send(Nette\Mail\Message $mail, $messageId = NULL) {
		try {
			parent::send($mail);

			if ($messageId) {
				$this->deleteMessage($messageId);
			}

		} catch (\Exception $e) {
			// zalogování chyby
			\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);

			// uložení emailu do DB pro pozdější odeslání
			$this->saveMessage($mail, $messageId);
		}
	}

	/**
	 * Znovu odešle všechny zprávy z DB
	 */
	public function resendMessages() {
		$messages = $this->getDatabase()->query("SELECT * FROM " . self::DB_TABLE);

		foreach ($messages->fetchAll() as $message) {
			$this->send(unserialize($message["message"]), $message["id"]);
		}
	}

	/**
	 * Vrátí všechny zprávy z DB
	 * @return array
	 */
	public function getMessagesQueue() {
		return $this->getDatabase()->query("SELECT * FROM " . self::DB_TABLE)->fetchAll();
	}

	/**
	 * @return \Nette\Database\Connection
	 */
	protected function getDatabase() {
		if (empty($this->connection)) {

			$options = [];
			if (!empty($this->options["sqlite"]["options"])) {
				$options = $this->options["sqlite"]["options"];
			}

			$this->connection = new \Nette\Database\Connection("sqlite:" . $this->options["sqlite"]["path"] . "", NULL, NULL, $options);
		}

		return $this->connection;
	}

	/**
	 * @param \Nette\Mail\Message $message
	 */
	protected function saveMessage(\Nette\Mail\Message $message, $messageId = NULL) {

		// nastaví aktuální datum a čas odeslání
		if ($messageId) {
			$this->updateMessage($messageId);
			return;
		}

		$date = (new \DateTime)->format("Y-m-d H:i:s");
		$data = [
			"subject" => $message->getSubject(),
			"to" => self::parseHeaderParam($message->getHeader("To")),
			"cc" => self::parseHeaderParam($message->getHeader("Cc")),
			"bcc" => self::parseHeaderParam($message->getHeader("Bcc")),
			"message" => serialize($message),
			"created" => $date,
			"last_send" => $date,
		];

		$this->getDatabase()->query("INSERT INTO " . self::DB_TABLE . " ?", $data);
	}

	/**
	 * @param array $params
	 * @return string
	 */
	protected static function parseHeaderParam($params = []) {
		$items = [];
		foreach ($params as $email => $name) {
			$item = $email;

			if ($name) {
				$item .= ":" . $name;
			}

			$items[] = $item;
		}

		return implode(",", $items);
	}

	/**
	 * ID zprávy z DB
	 * @param integer $messageId
	 */
	protected function deleteMessage($messageId) {
		$this->getDatabase()->query("DELETE FROM " . self::DB_TABLE . " WHERE id = ?", $messageId);
	}

	/**
	 * ID zprávy z DB
	 * @param type $messageId
	 */
	protected function updateMessage($messageId) {
		$data = [
			"last_send" => (new \DateTime)->format("Y-m-d H:i:s"),
		];

		$this->getDatabase()->query("UPDATE " . self::DB_TABLE . " SET ? WHERE id = ?", $data, $messageId);
	}

}
