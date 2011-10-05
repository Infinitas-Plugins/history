<?php
	class BehaviorsGroupTest extends GroupTest {

		public $label = 'Behaviors';

		public function behaviorsGroupTest() {
			TestManager::addTestCasesFromDirectory($this, App::pluginPath('Companies') . 'tests' . DS . 'cases' . DS . 'behaviors');
		}
	}