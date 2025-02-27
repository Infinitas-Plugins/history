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
			if(!$event->Handler->shouldAutoAttachBehavior()) {
				return false;
			}
			
			if((!empty($event->Handler->revision) && $event->Handler->revision) && $event->Handler->shouldAutoAttachBehavior('History.Revision')) {
				$this->__attachRevision($event);
			}

			if((!empty($event->Handler->drafted) && $event->Handler->drafted) && $event->Handler->shouldAutoAttachBehavior('History.Drafted')) {
				$this->__attachDrafted($event);
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
		private function __attachRevision($event) {
			if($this->__shouldIgnore($event)) {
				return false;
			}
			
			if(Configure::read('debug') && !in_array($event->Handler->table . Configure::read('History.suffix'), $event->Handler->getTables())) {
				$notice = sprintf(
					__d('history', 'Trying to attatch Revisioning to %s (%s) but there is no revision table', true),
					prettyName($event->Handler->alias),
					$event->Handler->name
				);
				user_error($notice, E_USER_NOTICE);
			}
			$config = Configure::read('History.behaviorConfig');
			$config['model'] = $event->Handler->plugin . '.' . $event->Handler->name;
			
			return $event->Handler->Behaviors->attach('History.Revision', $config);
		}

		private function __shouldIgnore($event) {
			if(substr($event->Handler->table, - strlen(Configure::read('History.suffix'))) == Configure::read('History.suffix')) {
				return true;
			}
			
			if(substr($event->Handler->table, - strlen(Configure::read('Drafted.suffix'))) == Configure::read('Drafted.suffix')) {
				return true;
			}
		}

		/**
		 * @brief attach the drafted behavior to models that require it.
		 *
		 * @access private
		 *
		 * @param Event $event the event that was triggered
		 *
		 * @return bool see BehaviorCollection::attach()
		 */
		private function __attachDrafted($event) {
			if($this->__shouldIgnore($event)) {
				return false;
			}

			if(Configure::read('debug') && !in_array($event->Handler->table . Configure::read('Drafted.suffix'), $event->Handler->getTables())) {
				$notice = sprintf(
					__d('history', 'Trying to attatch Drafted to %s (%s) but there is no draft table', true),
					prettyName($event->Handler->alias),
					$event->Handler->name
				);
				user_error($notice, E_USER_NOTICE);
			}

			return $event->Handler->Behaviors->attach('History.Drafted', Configure::read('Drafted.behaviorConfig'));
		}
	}