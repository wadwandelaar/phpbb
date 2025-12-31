<?php

namespace ochre\portal_trophy\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	private $root_path;
	private $trophy_map;

	public function __construct($root_path)
	{
		$this->root_path = $root_path;
		$this->trophy_map = null;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.viewforum_modify_topicrow' => 'add_trophy',
		];
	}

	public function add_trophy($event)
	{
		$topic_row = $event['topic_row'];
		$row = $event['row'];
		$topic_id = (int) $row['topic_id'];

		$map = $this->get_trophy_map();
		if (isset($map[$topic_id])) {
			$topic_row['S_TROPHY'] = true;
			$topic_row['TROPHY_TITLE'] = $map[$topic_id]['title'];
			$topic_row['TROPHY_TYPE'] = $map[$topic_id]['type'];
		} else {
			$topic_row['S_TROPHY'] = false;
		}

		$event['topic_row'] = $topic_row;
	}

	private function get_trophy_map()
	{
		if ($this->trophy_map !== null) {
			return $this->trophy_map;
		}

		$resolved_root = realpath($this->root_path);
		$root = $resolved_root !== false ? $resolved_root : $this->root_path;
		$portal_path = dirname(rtrim($root, '/')) . '/portal/data/portal_blocks.json';
		if (!is_file($portal_path)) {
			$this->trophy_map = [];
			return $this->trophy_map;
		}

		$data = json_decode((string) file_get_contents($portal_path), true);
		if (!is_array($data)) {
			$this->trophy_map = [];
			return $this->trophy_map;
		}

		$labels = [
			'todays_choice' => [
				'title' => "Today's choice",
				'type' => 'today',
			],
			'yesterdays_choice' => [
				'title' => "Yesterday's choice",
				'type' => 'yesterday',
			],
			'weekfoto' => [
				'title' => 'Weekfoto',
				'type' => 'weekfoto',
			],
		];

		$map = [];
		foreach ($labels as $key => $label) {
			if (empty($data[$key]['link'])) {
				continue;
			}
			$topic_id = $this->extract_topic_id($data[$key]['link']);
			if ($topic_id) {
				$map[$topic_id] = [
					'title' => $label['title'],
					'type' => $label['type'],
				];
			}
		}

		$this->trophy_map = $map;
		return $this->trophy_map;
	}

	private function extract_topic_id($link)
	{
		$parts = parse_url($link);
		if (!empty($parts['query'])) {
			parse_str($parts['query'], $query);
			if (!empty($query['t'])) {
				return (int) $query['t'];
			}
		}

		if (preg_match('/[?&]t=(\d+)/', $link, $matches)) {
			return (int) $matches[1];
		}

		return 0;
	}
}
