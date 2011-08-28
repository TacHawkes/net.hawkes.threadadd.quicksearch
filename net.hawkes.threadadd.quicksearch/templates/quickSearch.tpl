{if $this->user->getPermission('user.message.canUseThreadQuickSearch') && $this->user->getUserOption('showThreadQuickSearch')}
<script type="text/javascript" src="{@RELATIVE_WCF_DIR}js/StringUtil.class.js"></script>
<script type="text/javascript" src="{@RELATIVE_WBB_DIR}js/ThreadQuickSearch.class.js"></script>
<script type="text/javascript">
	//<![CDATA[
	threadQuickSearch = new ThreadQuickSearch('subject', {
		showAlways: {@THREAD_ADD_SHOW_ALWAYS},
	 	showTime: {@THREAD_ADD_QUICKSEARCH_SHOWTIME},
	 	showReplies: {@THREAD_ADD_QUICKSEARCH_SHOWREPLIES},
	 	showSearchLink: {@THREAD_ADD_QUICKSEARCH_SHOWSEARCHLINK},
	 	boardID: {@$board->boardID},
	 	boxClass: '{THREAD_ADD_QUICKSEARCH_STYLE}',
	 	closeIcon: '{icon}closeS.png{/icon}',
	 	threadsIcon: '{icon}similarThreadsM.png{/icon}',
	 	boardIcon: '{icon}boardS.png{/icon}',
	 	threadIcon: '{icon}threadS.png{/icon}',
	 	searchIcon: '{icon}searchM.png{/icon}',
	 	langWbbThreadSimilarThreads: '{lang}wbb.thread.similarThreads{/lang}',
	 	langWbbThreadAddSimilarThreadsquickSearch1: '{lang}wbb.threadAdd.similarThreads.quickSearch.1{/lang}',
	 	langWbbThreadAddSimilarThreadsquickSearch2: '{lang}wbb.threadAdd.similarThreads.quickSearch.2{/lang}',
	 	langWbbBoardThreadsReplies: '{lang}wbb.board.threads.replies{/lang}',
	 	langWbbThreadQuickSearchLink: '{lang}wbb.thread.quickSearch.link{/lang}',
	 	langWcfGlobalButtonClose: '{lang}wcf.global.button.close{/lang}'
	});
	//]]>
</script>
{/if}