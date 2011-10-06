<?php
	App::import('lib', 'Libs.InfinitasAppShell');
	
	class HistoryShell extends InfinitasAppShell {
		public $uses = array(
			'Plugin'
		);

		public $tasks = array('Infinitas');

		/**
		 * @brief map options to methods
		 * 
		 * @var array
		 */
		private $__options = array(
			'G' => 'generate_revision_tables',
			'D' => 'delete_revision_tables',
			'Q' => 'quit'
		);

		/**
		 * @brief show the main options for the history shell
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function main() {
			Configure::write('debug', 2);
			$this->Infinitas->h1('Infinitas History');

			$this->Infinitas->out('[G]enerate Revision Tables');
			$this->Infinitas->out('[Q]uit');

			$method = strtoupper($this->in(__('What would you like to do?', true), array_keys($this->__options)));

			$method = $this->__options[$method];
			if(!is_callable(array($this, $method))){
				$this->out(__('You have made an invalid selection. Please choose an option from above.', true));
			}
			else{
				$this->{$method}();
			}

			$this->main();
		}

		/**
		 * @brief generate the tables for a revision table
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function generate_revision_tables() {
			$plugin = current($this->_selectPlugins());
			$models = $this->_selectModels($plugin, true);

			foreach($models as $model) {
				$this->__generateRevisionTable($plugin . '.' . $model);
			}
			$this->interactive('')
		}

		private function __generateRevisionTable($model) {
			if(!class_exists('CakeSchema')) {
				App::import('Core', 'CakeSchema');
			}
			
			$Model = ClassRegistry::init($model);

			$revisionTable = sprintf($Model->tablePrefix . $Model->table . '_rev');

			$Db = ConnectionManager::getDataSource($Model->useDbConfig);
			$Schema = new CakeSchema(array('name' => $Model->useDbConfig, 'connection' => $Model->useDbConfig));
			$Schema->_build(array($revisionTable => $Model->schema()));

			$listOfFieldsToIgnore = array('lft', 'rght');
			$Schema->tables[$revisionTable]['version_id'] = $Schema->tables[$revisionTable][$Model->primaryKey];
			unset($Schema->tables[$revisionTable][$Model->primaryKey]['key']);

			foreach($listOfFieldsToIgnore as $ignore) {
				unset($Schema->tables[$revisionTable][$ignore]);
			}

			$this->interactive(sprintf('Generating revision table %s for %s', $revisionTable, prettyName($Model->alias)));
			return $Model->query($Db->createSchema($Schema));
		}

		/**
		 * @brief delete the tables for a revision table
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function delete_revision_tables() {

		}
	}