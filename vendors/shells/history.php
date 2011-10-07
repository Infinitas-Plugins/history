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
			'DG' => 'generate_draft_tables',
			'I' => 'init_revision_tables',
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
			if(!class_exists('CakeSchema')) {
				App::import('Core', 'CakeSchema');
			}
			App::import('libs', 'ClearCache.ClearCache');
			$this->ClearCache = new ClearCache();
			
			Configure::write('debug', 2);
			$this->Infinitas->h1('Infinitas History');

			$this->Infinitas->out('[G]enerate Revision Tables');
			$this->Infinitas->out('[DG]enerate Draft Tables');
			$this->Infinitas->out('[I]nit Revision Tables');
			$this->Infinitas->out('[D]elete Revision Tables');
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
			$this->interactive(sprintf('Created %d tables for revision', count($models)));
		}

		public function init_revision_tables() {
			$plugin = current($this->_selectPlugins());
			$models = $this->_selectModels($plugin, true);

			foreach($models as $model) {
				$this->__initRevisionTable($plugin . '.' . $model);
			}
			$this->interactive(sprintf('Initialised %d tables for revision', count($models)));
		}

		/**
		 * @brief delete the tables for a revision table
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function delete_revision_tables() {
			$Db = ConnectionManager::getDataSource($this->Plugin->useDbConfig);
			$Schema = new CakeSchema(array('name' => $this->Plugin->useDbConfig, 'connection' => $this->Plugin->useDbConfig));

			$tables = $this->__getTables();
			foreach($tables as $table) {
				$Schema->tables = array($table => array());
				$this->Plugin->query($Db->dropSchema($Schema));
			}

			$this->interactive(sprintf('Droped %d revision tables', count($tables)));
		}

		/**
		 * @brief generate the tables for a revision table
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function generate_draft_tables() {
			$plugin = current($this->_selectPlugins());
			$models = $this->_selectModels($plugin, true);

			foreach($models as $model) {
				$this->__generateDraftTable($plugin . '.' . $model);
			}
			$this->interactive(sprintf('Created %d tables for revision', count($models)));
		}

		/**
		 * @brief generate tables for revisions on selected models
		 *
		 * This will get the details of the real table and add / remove fields as
		 * needed by the revision behavior. It then uses CakeSchema and the DBO
		 * to generate the tables with the real models connection
		 *
		 * @access private
		 *
		 * @param string $model the model that the revision table is for
		 *
		 * @return bool output from Model::query()
		 */
		private function __generateRevisionTable($model) {
			if(!$this->__dbConnection($model, 'revision')) {
				return false;
			}
			
			$this->Schema->tables[$this->__revisionTableName()]['version_id'] = $this->Schema->tables[$this->__revisionTableName()][$this->CurrentModel->primaryKey];
			$this->Schema->tables[$this->__revisionTableName()]['version_created'] = array(
				'type' => 'datetime',
				'null' => 1,
				'default' => null,
				'length' => null
			);

			$this->Schema->tables[$this->__revisionTableName()]['revision_ordering'] = array(
				'type' => 'integer',
				'null' => 1,
				'default' => null,
				'length' => 10
			);

			unset($this->Schema->tables[$this->__revisionTableName()][$this->CurrentModel->primaryKey]['key']);

			$listOfFieldsToIgnore = array('lft', 'rght');
			foreach($listOfFieldsToIgnore as $ignore) {
				unset($this->Schema->tables[$this->__revisionTableName()][$ignore]);
			}

			$this->interactive(sprintf('Generating revision table %s for %s', $this->__revisionTableName(), prettyName($this->CurrentModel->alias)));
			if($this->CurrentModel->query($this->Db->createSchema($this->Schema))) {
				//$this->ClearCache->run();
				//return $this->__initRevisionTable();
			}

			return true;
		}

		private function __initRevisionTable($model = null) {
			if($model && !$this->__dbConnection($model, 'revision')) {
				return false;
			}

			$this->CurrentModel->Behaviors->attach(
				'History.Revision',
				Configure::read('History.behaviorConfig')
			);

			$this->CurrentModel->ShadowModel->Behaviors->detach('Sequence');
			$this->interactive(sprintf('Initialising revision table %s for %s', $this->__revisionTableName(), prettyName($this->CurrentModel->alias)));
			$this->CurrentModel->initializeRevisions();

			$this->CurrentModel->ShadowModel->updateAll(array($this->CurrentModel->alias . '.revision_ordering' => 1));
		}

		/**
		 * @brief generate tables for drafts on selected models
		 *
		 * This will get the details of the real to create the draft table for the
		 * behavior to work
		 *
		 * @access private
		 *
		 * @param string $model the model that the revision table is for
		 *
		 * @return bool output from Model::query()
		 */
		private function __generateDraftTable($model) {
			if(!$this->__dbConnection($model, 'draft')) {
				return false;
			}

			$this->Schema->tables[$this->__draftTableName()]['draft_id'] = $this->Schema->tables[$this->__draftTableName()][$this->CurrentModel->primaryKey];

			unset($this->Schema->tables[$this->__draftTableName()][$this->CurrentModel->primaryKey]['key']);

			$listOfFieldsToIgnore = array('lft', 'rght');
			foreach($listOfFieldsToIgnore as $ignore) {
				unset($this->Schema->tables[$this->__draftTableName()][$ignore]);
			}

			$this->interactive(sprintf('Generating draft table %s for %s', $this->__draftTableName(), prettyName($this->CurrentModel->alias)));
			if($this->CurrentModel->query($this->Db->createSchema($this->Schema))) {
				//$this->ClearCache->run();
				//return $this->__initRevisionTable();
			}

			return true;
		}

		/**
		 * @brief set up the connection for the revision table
		 *
		 * @access private
		 * 
		 * @param string $model the name of a model to load (Model.Plugin format)
		 *
		 * @return bool
		 */
		private function __dbConnection($model, $type = null) {
			$this->CurrentModel = ClassRegistry::init($model);

			if($type == 'revision') {
				$tableName = '__revisionTableName';
			}
			else if($type == 'draft') {
				$tableName = '__draftTableName';
			}

			$this->Db = ConnectionManager::getDataSource($this->CurrentModel->useDbConfig);
			$this->Schema = new CakeSchema(array('name' => $this->CurrentModel->useDbConfig, 'connection' => $this->CurrentModel->useDbConfig));
			$this->Schema->_build(array($this->{$tableName}() => $this->CurrentModel->schema()));

			return true;
		}

		/**
		 * @breif generate the name for the revision table
		 *
		 * @access private
		 *
		 * @return string the name of the revision table
		 */
		private function __revisionTableName() {
			return sprintf($this->CurrentModel->tablePrefix . $this->CurrentModel->table . Configure::read('History.suffix'));
		}

		/**
		 * @breif generate the name for the revision table
		 *
		 * @access private
		 *
		 * @return string the name of the revision table
		 */
		private function __draftTableName() {
			return sprintf($this->CurrentModel->tablePrefix . $this->CurrentModel->table . Configure::read('Drafted.suffix'));
		}


		private function __getTables() {
			$Model = new AppModel(null, false, $this->Plugin->useDbConfig);
			$tables = $Model->getTables();

			$return = array();
			foreach($tables as $table) {
				if(substr($table, - strlen(Configure::read('History.suffix'))) == Configure::read('History.suffix')) {
					$return[] = $table;
				}
			}

			return $return;
		}
	}