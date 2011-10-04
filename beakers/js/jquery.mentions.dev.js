/*!
 * jQuery @mentions plugin, v1.1
 *
 * Copyright 2011, Paul Gibbs <paul@byotos.com>.
 *
 * Licensed under GPL Version 2 and/or 3.
 * http://www.gnu.org/licenses/gpl-2.0.html
 * http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Requires jQuery 1.6+
 */

/*
 * @todo Add keyboard up/down/return/tab support.
 * @todo Use contentEditable.
 */

(function($) {
  $.fn.mentions = function(options) {
		options = $.extend({}, $.fn.mentions.defaults, options);

		var key = {
			up    : 38,
			down  : 40,
			enter : 13,
			esc   : 27,
			at    : 64
		};

		return this.each(function() {
			var input_obj   = $(this),
			found_at_token  = false,
			results         = null,
			results_started = false,
			previous_query  = '',
			query           = '',
			timer           = 0,

			// Metadata plugin support - http://docs.jquery.com/Plugins/Metadata/metadata
			o = $.meta ? $.extend({}, options, input_obj.data()) : options,

			// Have fun parsing this regex. I had fun writing it.
			at_regex = new RegExp("@[\\w\\\\\\.\\-]+\\s?(?:[\\w\\\\\\.\\-]+\\s?){0," + (o.max_name_parts - 1) + "}(?:[\\w\\\\\\.\\-]w+)?$");

			// Add results box
			function add_panel() {
				var html = '<ul id="mentions-autosuggest"></ul>';

				if (o.resultsbox) {
					if ('string' == typeof o.resultsbox) {
						if ('prepend' == o.resultsbox_position) {
							$(o.resultsbox).prepend(html);
						} else if ('append' == o.resultsbox_position) {
							$(o.resultsbox).append(html);
						} else if ('before' == o.resultsbox_position) {
							$(o.resultsbox).before(html);
						} else if ('after' == o.resultsbox_position) {
							$(o.resultsbox).after(html);
						}

					// Assume jQuery object
					}	else if (o.resultsbox) {
						if ('prepend' == o.resultsbox_position) {
							o.resultsbox.prepend(html);
						} else if ('append' == o.resultsbox_position) {
							o.resultsbox.append(html);
						} else if ('before' == o.resultsbox_position) {
							o.resultsbox.before(html);
						} else if ('after' == o.resultsbox_position) {
							o.resultsbox.after(html);
						}
					}

				// Fallback to parent's element
				} else {
					if ('prepend' == o.resultsbox_position) {
						input_obj.parent().prepend(html);
					} else if ('append' == o.resultsbox_position) {
						input_obj.parent().append(html);
					} else if ('before' == o.resultsbox_position) {
						input_obj.parent().before(html);
					} else if ('after' == o.resultsbox_position) {
						input_obj.parent().after(html);
					}
				}

				results = $('#mentions-autosuggest');
			}

			// Close the results panel
			function close_panel() {
				clearTimeout(timer);

				results.slideUp('slow', function() {
					results.empty();

					// Move caret to end
					input_obj[0].setSelectionRange(input_obj.val().length, input_obj.val().length);
					input_obj.focus();
				});

				found_at_token = results_started = false;
				previous_query = '';
			}

			// Listen for input
			function handle_input() {
				if (!found_at_token) {
					clearTimeout(timer);
					return;
				}

				query = at_regex.exec(input_obj.val());
				if (!query) {
					previous_query = query = '';
					return;
				}

				if (previous_query == query) {
					return;
				}

				// New query is new
				if ($.isFunction(o.start)) {
					o.start.call(this, query);
				}

				// BP DTheme 1.5 workaround for the "what's new" box
				if ( 'whats-new-options' == $(o.resultsbox).attr('id') && 'inherit' != $(o.resultsbox).css('overflow') ) {
					$(o.resultsbox).css('overflow', 'inherit').css('position', 'relative').css('z-index', '100');
				}

				if (!results_started) {
					results.css('width', input_obj.outerWidth(false)-4).html('<li class="section ajaxloader"><p><span class="ajax-loader" style="display: inline"></span>' + BPMentions.searching + '</p></li>').show();
				} else {
					// If panel is open, add spinner to top
					results.css('width', input_obj.outerWidth(false)-4).prepend('<li class="section ajaxloader"><p><span class="ajax-loader" style="display: inline"></span>' + BPMentions.searching + '</p></li>').show();
				}

				// Are results cached?
				var cache = results.data('bpl_' + query);
				if (cache) {
					receive_results(cache, 'success', null, query);

				} else {
					// Make ajax request
					$.ajax({
						dataType : o.dataType,
						type     : 'POST',
						url      : ajaxurl,
						data     : {
							'action' : 'activity_mention_autosuggest',
							'cookie' : encodeURIComponent(document.cookie),
							'format' : o.dataType,
							'limit'  : o.max_suggestions,
							'search' : query
						},

						// Callbacks
						error    : o.error,
						success  : function(response, textStatus, jqXHR) { receive_results( response, textStatus, jqXHR, query ); }
					});
				}

				previous_query = query;
			}

			// Data received from ajax request
			function receive_results(response, textStatus, jqXHR, searchQuery) {
				if ('success' == textStatus) {
					results.data('bpl_' + searchQuery, response);
				}

				// If you've asked for JSON, handle that in the success callback.
				if ($.isFunction(o.success)) {
					o.success.call(response, textStatus, jqXHR);
				}

				if ('html' == o.dataType) {
					// Fade out the loading spinner
					results.find('li.ajaxloader').fadeOut('fast', function() {
						$(this).remove();

						// Missing required parameter
						if ('-1' == response) {
							if ($.isFunction(o.error)) {
								o.error.call(response, textStatus, jqXHR);
							}

							results.html('<li class="section error"><p><span>' + BPMentions.error1 + '</span> ' + BPMentions.error2 + '</p></li>');
							return;

						// No results
						} else if ( '0' == response ) {
							results.html('<li class="section error"><p><span>' + BPMentions.noresults1 + '</span> ' + BPMentions.noresults2 + '</p></li>');

						// Insert results
						} else {
							results.css('width', input_obj.outerWidth(false)-4);

							// Slide panel open if this is the first search
							if (!results_started) {
								results.slideUp('slow', function() {
									results.html(response).slideDown('slow');
								});

							} else {
								results.html(response);
							}
						}

						results_started = true;
					});

					if ($.isFunction(o.complete)) {
						o.complete.call(response, textStatus, jqXHR);
					}
				}
			}

			// Put selected name into input_obj
			function insert_name(name) {
				input_obj.val(input_obj.val().substring(0, input_obj.val().lastIndexOf('@')) + '@' + name + ' ');
				close_panel();
			}

			$(function() {
				add_panel();

				// Listen for input
				input_obj.bind('keypress.mentions', function(event) {
					if (key.at == event.which && !found_at_token) {
						found_at_token = true;

					// Start listening for input after "@" key
					} else if (found_at_token) {
						clearTimeout(timer);

						if (results_started) {
							timer = setTimeout(handle_input, 800);
						} else {
							// Do the first search very quickly
							timer = setTimeout(handle_input, 350);
						}
					}
				});

				// Escape / return (cancel)
				input_obj.bind('keyup.mentions', function(event) {
					if ((key.esc == event.keyCode || key.enter == event.keyCode) && results_started) {
						event.preventDefault();
						close_panel();
					}
				});

				// Defocus
				input_obj.bind('focusout.mentions', function(event) {
					if (results_started) {
						close_panel();
					}
				});

				// Click
				results.delegate('li:not(.section)', 'click.mentions', function(event) {
					event.preventDefault();
					insert_name($(this).attr('class'));
				});
			});
		});
  };

	$.fn.mentions.defaults = {
		// Options
		'resultsbox_position' : 'append', // Used with resultsbox. How to glue the results panel to the results box; "append", "prepend", "after", "before".
		'dataType'            : 'html',  // Type of data expected back from the server. Supports 'html' and 'json'.
		'max_name_parts'      : 3,       // Number of word parts that are identified as a name; i.e. "Boone B Gorges."
		'max_suggestions'     : 6,       // Max number of suggestions to return.
		'resultsbox'          : '',      // Used if dataType=html. jQuery identifier or object to append the results' container box to. If not set, defaults to "this'" parent element.

		// Callbacks
		'start'               : null,    // After a pattern match, before anything else.
		'success'             : null,    // When data received (before complete).
		'complete'            : null,    // When we're finished (after success).
		'error'               : null     // On any type of error.
	};
})(jQuery);