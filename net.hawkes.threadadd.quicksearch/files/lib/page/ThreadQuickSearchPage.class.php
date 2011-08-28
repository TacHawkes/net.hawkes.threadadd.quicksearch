<?php
// wbb imports
require_once(WBB_DIR.'lib/data/board/Board.class.php');
require_once(WBB_DIR.'lib/data/thread/ViewableThread.class.php');

// wcf imports
require_once(WCF_DIR.'lib/page/AbstractPage.class.php');
require_once(WCF_DIR.'lib/data/message/search/SearchEngine.class.php');

/**
 * Outputs a JS return for the JS thread quick search
 *
 * @author	Oliver Kliebisch
 * @copyright	2008-2010 Oliver Kliebisch
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package     net.hawkes.threadadd.quicksearch
 * @subpackage  page
 * @category    Burning Board
 */
class ThreadQuickSearchPage extends AbstractPage {
        // search parameters
	public $query = '';
        public $searchHash = '';
        public $searchData = array();
        public $searchConditions;
        public $searchID;
        public $additionalData;
        public $boardID;
        public $sortField = SEARCH_DEFAULT_SORT_FIELD;
        public $sortOrder = SEARCH_DEFAULT_SORT_ORDER;

        protected static $delimiters = '[\s\x21-\x2C\x2E\x2F\x3A-\x40\x5B-\x60\x7B-\x7E]';

        /**
         * @see Page::readParameters()
         */
        public function readParameters() {
                parent::readParameters();

                if (isset($_REQUEST['query'])) {
                        $this->query = StringUtil::trim($_REQUEST['query']);

                        // init search data
                        $this->searchData['additionalData']['post']['boardIDs'] = array();
                        $this->searchData['additionalData']['post']['findAttachments'] = 0;
                        $this->searchData['additionalData']['post']['findPolls'] = 0;
                        $this->searchData['additionalData']['post']['findThreads'] = $this->getConstant('SEARCH_FIND_THREADS');
                        $this->searchData['additionalData']['post']['findUserThreads'] = 0;
                        $this->searchData['additionalData']['post']['threadID'] = 0;
                        if (isset($_REQUEST['boardID']) && THREAD_ADD_QUICKSEARCH_LOCAL_SEARCH) {
                                $this->searchData['additionalData']['post']['boardIDs'] = array(intval($_REQUEST['boardID']));
                        }

                        if (CHARSET != 'UTF-8') $this->query = StringUtil::convertEncoding('UTF-8', CHARSET, $this->query);
                        $stopWords = explode("\n",  preg_replace("/\r+/", '', StringUtil::toLowerCase(THREAD_ADD_QUICKSEARCH_STOPWORDS)));
                        $stopWords = ArrayUtil::trim($stopWords);

                        $this->query = StringUtil::toLowerCase($this->query);
                        $textSplit = preg_split("!".self::$delimiters."+!", $this->query);

                        foreach ($textSplit as $key => $word) {
                                foreach ($stopWords as $stopWord) {
                                        if ($stopWord == $word) {
                                                unset($textSplit[$key]);
                                                continue 2;
                                        }
                                }

                                foreach ($stopWords as $stopWord) {
                                        if (StringUtil::indexOf($stopWord, '*') !== false) {
                                                $stopWord = StringUtil::replace('\*', '.*', preg_quote($stopWord));
                                                if (preg_match('!^'.$stopWord.'$!', $word)) {
                                                        unset($textSplit[$key]);
                                                        continue 2;
                                                }
                                        }
                                }
                        }

                        $this->query = implode(" ", $textSplit);

                        // dirty fix for a commented line by WoltLab :/
                        $_POST['findThreads'] = 1;
                }
        }
        /**
         * Checks the search index whether an active search session is available.
         *
         * @return string $postIDs
         */
        protected function checkSearchIndex() {
                $threadIDs = '';
                if (!empty($this->query)) {
                        $sql = "SELECT	*
				FROM	wcf".WCF_N."_search
				WHERE	searchHash = '".escapeString($this->searchHash)."'
				AND	searchType = 'messages'
				AND	userID = ".WCF::getUser()->userID."
				AND	searchDate > ".(TIME_NOW - 1800);
                        $row = WCF::getDB()->getFirstRow($sql);
                        if (isset($row['searchID'])) {
                                $search = unserialize($row['searchData']);
                                if ($search['packageID'] != PACKAGE_ID) return $threadIDs;
                                $this->additionalData = $search['additionalData'];
                                foreach ($search['result'] as $match) {
                                        if (!empty($threadIDs)) $threadIDs .= ',';
                                        $threadIDs .= $match['messageID'];
                                }
                                $this->searchID = $row['searchID'];
                                $this->searchData = $search;
                                $this->query = $this->searchData['query'];
                                $this->sortOrder = $this->searchData['sortOrder'];
                                $this->sortField = $this->searchData['sortField'];
                        }
                }
                return $threadIDs;
        }

        /**
         * Prepares the search conditions
         *
         * @return void
         */
        protected function buildSearchParameters() {
                $postSearch = SearchEngine::getSearchTypeObject('post');
                $conditions = '';
                try {
                        $conditions = $postSearch->getConditions($this);
                } catch(Exception $e) {
                        echo '</threads>';
                        exit;
                }
                switch(THREAD_ADD_QUICKSEARCH_TYPE) {
                        case 1:
                                $this->searchConditions = array('post' => $conditions);
                                break;
                        case 2:
                                $this->searchConditions = array('post' => "messageTable.postID = (	SELECT	firstPostID
													FROM	wbb".WBB_N."_thread
													WHERE	threadID = messageTable.threadID)
									AND ".$conditions);
                                break;
                        case 3:
                                $this->searchConditions = array('post' => "thread.threadID IN (SELECT threadID
				FROM		wbb".WBB_N."_thread
				WHERE		MATCH(topic) AGAINST( '".$this->parseQuery($this->query)."' IN BOOLEAN MODE)) AND ".$conditions);
                }

                $this->searchHash = StringUtil::getHash(serialize(array($this->query, array('post'), $this->searchConditions , $this->sortField.' '.$this->sortOrder, PACKAGE_ID)));
        }

        /**
         * Because I confuse the search engine with an empty query in case of search type 3, I have to parse on my own
         *
         * @param	string	$query
         * @return	string
         */
        protected function parseQuery($query) {
                $queryArray = explode(' ', $query);
                if (count($queryArray) > 1) {
                        foreach ($queryArray as $key => $element) {
                                $queryArray[$key] = $element.'*';
                        }
                        $query = implode(' ', $queryArray);
                }
                return $query;
        }

        /**
         * Prepares and executes the search
         *
         * @return string	The found threadIDs
         */
        protected function searchSimilarThreads() {
                $this->buildSearchParameters();
                $threadIDs = $this->checkSearchIndex();
                if(empty($threadIDs) ) {
                        $search = new SearchEngine();

                        $matches = $search->search(((THREAD_ADD_QUICKSEARCH_TYPE == 3) ? "" : $this->query), array('post'), $this->searchConditions, $this->sortField.' '.$this->sortOrder, 1000 , true);
                        if (count($matches) == 0) {
                                return '';
                        }
                        foreach ($matches as $match) {
                                if (!empty($threadIDs)) $threadIDs .= ',';
                                $threadIDs .= $match['messageID'];
                        }
                        $additionalData = array();
                        foreach (SearchEngine::$searchTypeObjects as $type => $typeObject) {
                                $data = $typeObject->getAdditionalData();
                                if ($type == 'post') {
                                        $data['findThreads'] = 1;
                                }
                                if ($data) $additionalData[$type] = $data;
                        }
                        $this->additionalData = $additionalData;
                        $this->searchData = array(
                            'packageID' => PACKAGE_ID,
                            'query' => $this->query,
                            'result' => $matches,
                            'additionalData' => $additionalData,
                            'sortField' => $this->sortField,
                            'sortOrder' => $this->sortOrder,
                            'nameExactly' => 1,
                            'subjectOnly' => 0,
                            'fromDay' => 0,
                            'fromMonth' => 0,
                            'fromYear' => '',
                            'untilDay' => 0,
                            'untilMonth' => 0,
                            'untilYear' => '',
                            'username' => '',
                            'userID' => 0,
                            'types' => array('post'),
                            'alterable' => 1
                        );
                        $sql = "INSERT INTO	wcf".WCF_N."_search
					(userID, searchData, searchDate, searchType, searchHash)
					VALUES		(".WCF::getUser()->userID.",
					'".escapeString(serialize($this->searchData))."',
					".TIME_NOW.",
					'messages',
					'".escapeString($this->searchHash)."')";
                        WCF::getDB()->sendQuery($sql);
                        $this->searchID = WCF::getDB()->getInsertID();
                }

                return $threadIDs;
        }

        /**
         * @see Page::show()
         */
        public function show() {
                parent::show();

                // reset URI in session
		if (WCF::getSession()->lastRequestURI) {
			WCF::getSession()->setRequestURI(WCF::getSession()->lastRequestURI);
		}

                // Easteregg ;)
                header("Expires: Fri, 12 May 1989 07:43:00 GMT" );
                header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
                header("Cache-Control: no-cache, must-revalidate" );
                header("Pragma: no-cache" );
                header('Content-type: text/xml');
                echo "<?xml version=\"1.0\" encoding=\"".CHARSET."\"?>\n<threads>\n";

                if (!empty($this->query)) {
                        $threadIDs = $this->searchSimilarThreads(THREAD_ADD_QUICKSEARCH_TYPE);
                        if (empty($threadIDs)) {
                                echo '</threads>';
                                exit;
                        }
                        $postSearch = SearchEngine::getSearchTypeObject('post');
                        $postSearch->cacheMessageData($threadIDs, $this->additionalData['post']);
                        $excludedBoards = explode(',', THREAD_ADD_QUICKSEARCH_EXCLUDED_BOARDS);
                        $threadIDs = explode(',', $threadIDs);
                        $i = 0;
                        foreach($threadIDs as $threadID) {
                                if ($i == 5) break;
                                $similarThread = $postSearch->getMessageData($threadID, $this->additionalData);
                                if (!$similarThread) continue;
                                $similarThread = $similarThread['message'];
                                if (in_array($similarThread->boardID, $excludedBoards)) continue;
                                echo "\t<thread>\n";
                                echo "\t\t<firstPostPreview><![CDATA[".StringUtil::escapeCDATA($similarThread->firstPostPreview)."]]></firstPostPreview>\n";
                                echo "\t\t<boardID><![CDATA[".StringUtil::escapeCDATA($similarThread->boardID)."]]></boardID>\n";
                                echo "\t\t<title><![CDATA[".StringUtil::escapeCDATA(WCF::getLanguage()->get($similarThread->title))."]]></title>\n";
                                echo "\t\t<threadID><![CDATA[".StringUtil::escapeCDATA($similarThread->threadID)."]]></threadID>\n";
                                echo "\t\t<prefix><![CDATA[".StringUtil::escapeCDATA(WCF::getLanguage()->get($similarThread->prefix))."]]></prefix>\n";
                                echo "\t\t<topic><![CDATA[".StringUtil::escapeCDATA($similarThread->topic)."]]></topic>\n";
                                echo "\t\t<time><![CDATA[".StringUtil::escapeCDATA(DateUtil::formatShortTime(null, THREAD_ADD_QUICKSEARCH_LASTPOSTTIME ? $similarThread->lastPostTime : $similarThread->time))."]]></time>\n";
                                echo "\t\t<replies><![CDATA[".StringUtil::escapeCDATA($similarThread->replies)."]]></replies>\n";
                                echo "\t</thread>\n";
                                $i++;
                        }
                }
		echo "\t<searchID><![CDATA[".StringUtil::escapeCDATA($this->searchID)."]]></searchID>\n";
                echo "</threads>";
                exit;
        }

	/**
	 * Helper function to avoid errors with WBBLite 2.1
	 *
	 * @param	string		$name  Name of the constant
	 */
	private function getConstant($name) {
		if (defined($name)) {
			return constant($name);
		}

		else return null;
	}
}
?>