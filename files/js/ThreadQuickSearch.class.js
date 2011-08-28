/**
 * @author Oliver Kliebisch
 * @copyright 2008-2009 Oliver Kliebisch
 * @license LGPL
 */
var ThreadQuickSearch = Class
		.create( {
			/**
			 * Initializes the quick search
			 */
			initialize : function(element) {
				this.element = $(element);
				this.options = Object.extend( {
					showAlways : false,
					showTime : true,
					showReplies : true,
					showSearchLink : true,
					boardID : 0,
					boxClass : 'info',
					closeIcon : '',
					threadsIcon : '',
					boardIcon : '',
					threadIcon : '',
					searchIcon : '',
					langWbbThreadSimilarThreads : '',
					langWbbThreadAddSimilarThreadsquickSearch1 : '',
					langWbbThreadAddSimilarThreadsquickSearch2 : '',
					langWbbBoardThreadsReplies : '',
					langWbbThreadQuickSearchLink : '',
					langWcfGlobalButtonClose : ''
				}, arguments[1] || {});

				this.threads = new Array();
				this.searchID = 0;

				// prepare container
				this.element
						.up()
						.insert(
								'<div id="similarThreadsBox" style="display:none;"></div>');

				this.addEventListener();
			},

			/**
			 * Adds the listener to the specified input element
			 */
			addEventListener : function() {
				this.element.observe('blur', this.search.bind(this));
			},

			/**
			 * Removes the listener from the specified input element
			 */
			removeEventListener : function() {
				this.element.stopObserving('blur', this.search.bind(this));
			},

			/**
			 * Sends the AJAX Request to search for new threads
			 */
			search : function(event) {
				this.purgeThreads();
				this.searchID = 0;
				oldSearch = this.searchString;
				this.searchString = this.element.value;
				if (this.searchString != '' && this.searchString != oldSearch) {
					this.hide();
					new Ajax.Request('index.php?page=ThreadQuickSearch'
							+ SID_ARG_2ND + '&query='
							+ encodeURIComponent(this.searchString)
							+ '&boardID=' + this.options.boardID, {
						onSuccess : this.handleResult.bind(this)
					});
				}
			},

			/**
			 * Handles the AJAX result
			 */
			handleResult : function(result) {
				if (result.responseText != '') {
					var threads = result.responseXML
							.getElementsByTagName('threads');
					if (threads.length > 0) {
						for ( var i = 0; i < threads[0].childNodes.length; i++) {
							if (threads[0].childNodes[i].childNodes.length > 0
									&& threads[0].childNodes[i].nodeName == 'thread') {
								var firstPostPreview = '';
								var boardID = 0;
								var title = '';
								var threadID = 0;
								var prefix = '';
								var topic = '';
								var time = 0;
								var replies = 0;

								for ( var j = 0; j < threads[0].childNodes[i].childNodes.length; j++) {
									if (threads[0].childNodes[i].childNodes[j].nodeName == 'firstPostPreview') {
										firstPostPreview = threads[0].childNodes[i].childNodes[j].childNodes[0].nodeValue;
									}
									if (threads[0].childNodes[i].childNodes[j].nodeName == 'boardID') {
										boardID = threads[0].childNodes[i].childNodes[j].childNodes[0].nodeValue;
									}
									if (threads[0].childNodes[i].childNodes[j].nodeName == 'title') {
										title = threads[0].childNodes[i].childNodes[j].childNodes[0].nodeValue;
									}
									if (threads[0].childNodes[i].childNodes[j].nodeName == 'threadID') {
										threadID = threads[0].childNodes[i].childNodes[j].childNodes[0].nodeValue;
									}
									if (threads[0].childNodes[i].childNodes[j].nodeName == 'prefix') {
										prefix = threads[0].childNodes[i].childNodes[j].childNodes[0].nodeValue;
									}
									if (threads[0].childNodes[i].childNodes[j].nodeName == 'topic') {
										topic = threads[0].childNodes[i].childNodes[j].childNodes[0].nodeValue;
									}
									if (threads[0].childNodes[i].childNodes[j].nodeName == 'time') {
										time = threads[0].childNodes[i].childNodes[j].childNodes[0].nodeValue;
									}
									if (threads[0].childNodes[i].childNodes[j].nodeName == 'replies') {
										replies = threads[0].childNodes[i].childNodes[j].childNodes[0].nodeValue;
									}
								}

								thread = new Object.extend( {
									firstPostPreview : firstPostPreview,
									boardID : boardID,
									title : title,
									threadID : threadID,
									prefix : prefix,
									topic : topic,
									time : time,
									replies : replies
								});

								this.addThread(thread);
							}
						}

						var searchID = result.responseXML
								.getElementsByTagName('searchID');
						this.searchID = searchID[0].firstChild.nodeValue;
					}

					if (this.threads.length > 0) {
						this.showSearchResult();
					}
				}
			},

			/**
			 * Displays the search result
			 */
			showSearchResult : function() {
				var templateString = '<div class="#{boxClass} deletable" id="similarThreadsBox" style="display:none;">'
						+ '	<span style="float:right;">'
						+ '		<a href="javascript:threadQuickSearch.disable();" class="close deleteButton">'
						+ '			<img src="#{closeIcon}" alt="" title="#{langWcfGlobalButtonClose}" />'
						+ '		</a>'
						+ '	</span>'
						+ '	<p id="similarAnnounce">'
						+ (this.options.showAlways ? '#{langWbbThreadAddSimilarThreadsquickSearch2}'
								: '#{langWbbThreadAddSimilarThreadsquickSearch1}')
						+ '</p>';
				templateString += '<div id="similarThreadsContent"';
				if (this.options.showAlways == false) {
					templateString += ' style="display:none;"';
				}
				templateString += '>'
						+ '<div class="containerIcon">'
						+ '	<img src="#{threadsIcon}" alt="" />'
						+ '</div>'
						+ '<div class="containerContent">'
						+ '	<h3><strong>#{langWbbThreadSimilarThreads}</strong></h3>'
						+ '		<ul class="similarThreads" id="similarThreadsList">';
				this.threads
						.each(function(thread) {
							templateString += '<li title="'
									+ new StringUtil(thread.firstPostPreview)
											.encodeHTML()
									+ '">'
									+ '<ul class="breadCrumbs">'
									+ '	<li>'
									+ '		<a href="index.php?page=Board&boardID='
									+ thread.boardID
									+ SID_ARG_2ND
									+ '">'
									+ '			<img src="#{boardIcon}" alt="" />'
									+ '			<span>'
									+ new StringUtil(thread.title).encodeHTML()
									+ '</span>'
									+ '		</a>'
									+ unescape('%BB')
									+ '	</li>'
									+ '	<li>'
									+ '		<a href="index.php?page=Thread&threadID='
									+ thread.threadID
									+ SID_ARG_2ND
									+ '">'
									+ '			<img src="#{threadIcon}" alt="" />'
									+ '			<span class="prefix">'
									+ '				<strong>'
									+ new StringUtil(thread.prefix)
											.encodeHTML()
									+ '</strong>'
									+ '			</span>'
									+ '			<span>'
									+ new StringUtil(thread.topic).encodeHTML()
									+ '</span>'
									+ '		</a>'
									+ (this.options.showReplies ? '		<span>('
											+ thread.replies
											+ ' #{langWbbBoardThreadsReplies})</span>'
											: '')
									+ (this.options.showTime ? '		<span class="light">('
											+ thread.time + ')</span>'
											: '') + '	</li>' + '</ul>'
									+ '</li>';
						}.bind(this));

				templateString += '</ul>' + '<div>'
						+ '	<img src="#{searchIcon}" alt="" />'
						+ '	<a href="index.php?form=Search&searchID='
						+ this.searchID + '&highlight='
						+ encodeURIComponent(this.searchString) + SID_ARG_2ND
						+ '">' + '		#{langWbbThreadQuickSearchLink}' + '	</a>'
						+ '</div>' + '</div>';

				templateString += '</div>' + '</div>';
				boxTemplate = new Template(templateString);

				// get anchor
				anchor = $('similarThreadsBox');
				anchor.replace(boxTemplate.evaluate(this.options));
				new Effect.Parallel( [
						new Effect.BlindDown('similarThreadsBox', {
							sync : true,
							duration : 0.8
						}), new Effect.Appear('similarThreadsBox', {
							sync : true,
							duration : 0.8
						}) ], {
					duration : 0.8,
					queue : {
						position : 'end',
						scope : 'threadSimilarity'
					}
				});
			},

			/**
			 * Disables the quicksearch
			 */
			disable : function() {
				this.removeEventListener();
				this.hide();
			},

			/**
			 * Hides the box
			 */
			hide : function() {
				if ($('similarThreadsBox').visible()) {
					new Effect.Parallel( [
							new Effect.BlindUp('similarThreadsBox', {
								sync : true,
								duration : 0.8
							}), new Effect.Fade('similarThreadsBox', {
								sync : true,
								duration : 0.8
							}) ], {
						duration : 0.8,
						queue : {
							position : 'end',
							scope : 'threadSimilarity'
						}
					});
				}
			},

			/**
			 * Blinds down the thread list
			 */
			showThreads : function() {
				element = $('similarThreadsContent');
				if (element.visible() == false) {
					element.blindDown( {
						duration : 0.5
					});
					announce = $('similarAnnounce');
					announce
							.update(this.options.langWbbThreadAddSimilarThreadsquickSearch2);
					this.options.showAlways = true;
				}
			},

			/**
			 * Purges the thread stack from old results
			 */
			purgeThreads : function(event) {
				this.threads = new Array();
			},

			/**
			 * Adds a thread to the result stack
			 */
			addThread : function(thread) {
				this.threads.push(thread);
			},

			/**
			 * Sets the search ID of the last search
			 * 
			 * @deprecated
			 */
			setSearchID : function(id) {
				this.searchID = id;
			}
		});