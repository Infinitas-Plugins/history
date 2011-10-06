<?php
	class HistoryEvents extends AppEvents {
		/**
		 * @brief set up some configuration variables
		 *
		 * @access public
		 *
		 * @param Event $event the event that was triggered
		 * @param array $data data that is passed to the events
		 * 
		 * @return void
		 */
		public function onSetupConfig($event, $data = null) {
			Configure::load('History.config');
		}

		/**
		 * @brief attach behaviors to models
		 *
		 * @access public
		 *
		 * @param Event $event the event that was triggered
		 *
		 * @return void
		 */
		public function onAttachBehaviors($event = null) {
			if(!$event->Handler->shouldAutoAttachBehavior() || empty($event->Handler->revision) || !$event->Handler->revision) {
				return false;
			}

			if ($event->Handler->shouldAutoAttachBehavior('History.Revision')) {
				$this->__attachHistory($event);
			}
		}

		/**
		 * @brief attach the revision behavior to models that require it.
		 *
		 * @access private
		 *
		 * @param Event $event the event that was triggered
		 *
		 * @return bool see BehaviorCollection::attach()
		 */
		private function __attachHistory($event) {
			if(Configure::read('debug') && !in_array($event->Handler->table . '_revs', $event->Handler->getTables())) {
				$notice = sprintf(
					__d('history', 'Trying to attatch Revisioning to %s (%s) but there is no revision table', true),
					prettyName($event->Handler->alias),
					$event->Handler->name
				);
				user_error($notice, E_USER_NOTICE);
			}

			return $event->Handler->Behaviors->attach(
				'History.Revision',
				array(
					'ignore' => array(
						'views'
					)
				)
			);
		}
	}