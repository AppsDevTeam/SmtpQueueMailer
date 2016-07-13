<?php

namespace ADT\SmtpQueueMailer\Service;

use Nette;

class MessageQueue extends Nette\Object {

	/** @var array */
	protected $options;

	/** @var Nette\Database\Connection */
	protected $connection;

	/**
	 * @param array $options
	 */
	public function setOptions($options) {
		$this->options = $options;
	}

	/**
	 * @return \Nette\Database\Connection
	 */
	protected function getDatabase() {
		if (empty($this->connection)) {

			if (empty($this->options["path"]) || !file_exists($this->options["path"])) {
		//		throw new \Nette\InvalidArgumentException("Chybí povinný parametr 'path' nebo neexistuje soubor s sqlite databází.");
			}

			$options = [];
			if (!empty($this->options["options"])) {
				$options = $this->options["options"];
			}

			$this->connection = new \Nette\Database\Connection("sqlite:" . $this->options["path"] . "", NULL, NULL, $options);
		}

		return $this->connection;
	}

	/**
	 * @param \Nette\Mail\Message $message
	 */
	public function saveMessage(\Nette\Mail\Message $message, $messageId = NULL) {

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

		$this->getDatabase()->query("INSERT INTO messages ?", $data);
	}

	/**
	 * Vrátí všechny zprávy z DB
	 * @return array
	 */
	public function getMessagesQueue() {
		return $this->getDatabase()->query("SELECT * FROM messages")->fetchAll();
	}

	/**
	 * @param array $params
	 * @return string
	 */
	protected static function parseHeaderParam($params = []) {
		$items = [];
		foreach ((array) $params as $email => $name) {
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
	public function deleteMessage($messageId) {
		$this->getDatabase()->query("DELETE FROM messages WHERE id = ?", $messageId);
	}

	/**
	 * ID zprávy z DB
	 * @param integer $messageId
	 */
	public function updateMessage($messageId) {
		$data = [
			"last_send" => (new \DateTime)->format("Y-m-d H:i:s"),
		];

		$this->getDatabase()->query("UPDATE messages SET ? WHERE id = ?", $data, $messageId);
	}

}
