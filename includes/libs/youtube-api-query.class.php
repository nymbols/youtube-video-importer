<?php
class YouTube_API_Query{
		
	/**
	 * YouTube API server key
	 */
	private $server_key;
	
	/**
	 * YouTube API query base
	 */
	private $base = 'https://www.googleapis.com/youtube/v3/';
	
	/**
	 * Results per page
	 */
	private $per_page = 10;
	
	/**
	 * Store list statistics:
	 * - 
	 */
	private $list_info = array(
		'next_page' 	=> '', // stores next page token for searches of playlists
		'prev_page' 	=> '', // stores previous page token for searches or playlists
		'total_results' => 0, // stores total results from search or playlist
		'page_results' 	=> 0, // stores current page results
	);
	
	private $include_categories = false;
	
	/**
	 * Constructor, sets up a few variables
	 */
	public function __construct( $per_page = false, $include_categories = false ){

		$yt_api_key = yti_get_yt_api_key();
		if( $yt_api_key && yti_get_yt_api_key('validity') ){
			$this->server_key = $yt_api_key;
		}
		
		if( $per_page ){
			$this->per_page = absint( $per_page );
		}		
		
		$this->include_categories = (bool)$include_categories;		
	}
	
	/**
	 * Performs a search on YouTube
	 * 
	 * @param string $query - the search query
	 * @param string $page_token - next/previous page token
	 * @param string $order - results ordering ( values: date, rating, relevance, title or viewCount )
	 * 
	 * @return - array of videos or WP_Error if something went wrong
	 */
	public function search( $query, $page_token = '', $args = array() ){
		// get videos feed
		$videos = $this->_query_videos( 'search', $query, $page_token, $args );
		return $videos;
	}
	
	/**
	 * Get videos from a playlist from YouTube
	 * 
	 * @param string $query - YouTube playlist ID
	 * @param string $page_token - next/previous page token
	 * 
	 * @return - array of videos or WP_Error is something went wrong
	 */
	public function get_playlist( $query, $page_token = '' ){
		$videos = $this->_query_videos( 'playlist', $query, $page_token );
		return $videos;		
	}
	
	/**
	 * Get videos from a channel from YouTube
	 * 
	 * @param string $query - YouTube channel ID
	 * @param string $page_token - next/previous page token
	 * 
	 * @return - array of videos or WP_Error is something went wrong
	 */
	public function get_channel_uploads( $query, $page_token = '' ){
		$url = $this->_get_endpoint( 'channel_id', $query );
		if( is_wp_error( $url ) ){
			return $url;
		}			
		$channel 	= $this->_make_request( $url );
		// check for errors
		if( is_wp_error( $channel ) ){
			return $channel;
		}
		
		if( isset( $channel['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ) ){
			$playlist = $channel['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
		}else{
			// return WP error is playlist ID could not be found
			return $this->_generate_error( 'yt_api_channel_playlist_param_missing', __( 'User uploads playlist ID could not be found in YouTube API channel query response.', 'yti_video' ) );
		}
		
		$videos = $this->get_playlist( $playlist , $page_token );
		return $videos;		
	}
	
	/**
	 * Get videos from a user from YouTube
	 * 
	 * @param string $query - YouTube user ID
	 * @param string $page_token - next/previous page token
	 * 
	 * @return - array of videos or WP_Error is something went wrong
	 */
	public function get_user_uploads( $query, $page_token = '' ){
		$url = $this->_get_endpoint( 'user_channel', $query );
		if( is_wp_error( $url ) ){
			return $url;
		}
		$user 	= $this->_make_request( $url );

		// check for errors
		if( is_wp_error( $user ) ){
			return $user;
		}
		
		if( isset( $user['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ) ){
			$playlist = $user['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
		}else{
			// return WP error is playlist ID could not be found
			return $this->_generate_error( 'yt_api_user_playlist_param_missing', __( 'User uploads playlist ID could not be found in YouTube API user query response.', 'yti_video' ) );
		}
		
		$videos = $this->get_playlist( $playlist , $page_token );
		return $videos;	
	}
	
	/**
	 * Get details for a single video ID
	 * 
	 * @param string $query - YouTube video ID
	 * 
	 * @return - array of videos or WP_Error is something went wrong
	 */
	public function get_video( $query ){
		// make request for video details
		$url = $this->_get_endpoint( 'videos', $query );
		if( is_wp_error( $url ) ){
			return $url;
		}
		$result = $this->_make_request( $url );
		
		// check for errors
		if( is_wp_error( $result ) ){
			return $result;				
		}
		
		$videos = $this->_format_videos( $result );
		return $videos[0];	
	}
	
	/**
	 * Get details for multiple video IDs
	 * 
	 * @param string $query - YouTube video IDs comma separated or array of video ids
	 * 
	 * @return - array of videos or WP_Error is something went wrong
	 */
	public function get_videos( $query ){
		// query can be a list of comma separated ids or array of ids
		if( is_array( $query ) ){
			$query = implode(',', $query);
		}
		// make request for video details
		$url = $this->_get_endpoint( 'videos', $query );
		if( is_wp_error( $url ) ){
			return $url;
		}
		$result = $this->_make_request( $url );
		
		// check for errors
		if( is_wp_error( $result ) ){
			return $result;				
		}
		
		$videos = $this->_format_videos( $result );
		return $videos;
	}
	
	/**
	 * Get video categories based on IDs
	 * @param string $query - single ID or ids separated by comma
	 */
	public function get_categories( $query ){
		// make request
		$url = $this->_get_endpoint( 'categories', $query );
		if( is_wp_error( $url ) ){
			return $url;
		}
		$result = $this->_make_request( $url );
		
		// check for errors
		if( is_wp_error( $result ) ){
			return $result;				
		}
		
		$categories = array();
		foreach ( $result['items'] as $category ){
			$categories[ $category['id'] ] = $category['snippet']['title'];
		}
		
		return $categories;
	}
	
	/**
	 * Returns $this->list_info for query details.
	 */
	public function get_list_info(){
		return $this->list_info;
	}
	
	/**
	 * Queries videos based on a specific action.
	 * 
	 * @param string $action - search, playlist
	 * @param string $query - the query
	 * @param string $page_token - next/previous page token returned by API
	 * @param string $order - results order
	 * 
	 * @return - array of videos or WP_Error is something went wrong
	 */
	private function _query_videos( $action, $query, $page_token = '', $args = array() ){
		$url = $this->_get_endpoint( $action, $query, $page_token, $args );
		if( is_wp_error( $url ) ){
			return $url;
		}
		$result = $this->_make_request( $url );
		
		// check for errors
		if( is_wp_error( $result ) ){
			return $result;
		}			  
					
		// populate $this->list_info with the results returned from query
		$this->_set_query_info( $result );
	
		// get videos details
		$ids = array();
		foreach( $result['items'] as $video ){
			$key = 'id';
			switch( $action ){
				case 'playlist':
					$key = 'contentDetails';
				break;	
			}
			
			$ids[] = $video[ $key ]['videoId'];
		}
		// make request for video details
		$url = $this->_get_endpoint( 'videos', implode( ',', $ids ) );
		if( is_wp_error( $url ) ){
			return $url;
		}
		$result = $this->_make_request( $url );
		
		// check for errors
		if( is_wp_error( $result ) ){
			return $result;				
		}			
		
		$videos = $this->_format_videos( $result );
		return $videos;			
	}
	
	/**
	 * Used to set the pagination details from $this->list_info
	 * 
	 * @param array $result - the result returned by YouTube API
	 * @return void
	 */
	private function _set_query_info( $result ){
		// set default to empty
		$list_info = array(
			'next_page' 	=> '', // stores next page token for searches of playlists
			'prev_page' 	=> '', // stores previous page token for searches or playlists
			'total_results' => 0, // stores total results from search or playlist
			'page_results' 	=> 0, // stores current page results
		);	
		
		// set next page token if any		
		if( isset( $result['nextPageToken'] ) ){
			$list_info['next_page'] = $result['nextPageToken'];
		}
		// set prev page token if any
		if( isset( $result['prevPageToken'] ) ){
			$list_info['prev_page'] = $result['prevPageToken'];
		}
		// set total results
		if( isset( $result['pageInfo']['totalResults'] ) ){
			$list_info['total_results'] = $result['pageInfo']['totalResults'];
		}
		// set page results
		if( isset( $result['pageInfo']['resultsPerPage'] ) ){
			$list_info['page_results'] = $result['pageInfo']['resultsPerPage'];
		}
		
		$this->list_info = $list_info;
	}
	
	/**
	 * Arranges videos into a generally accepted format 
	 * to be used into the plugin
	 */
	private function _format_videos( $result ){
		$videos 	= array();
		$categories = array();
		
		foreach( $result['items'] as $video ){
			$videos[] = array(
				'video_id' 		=> $video['id'],
				// store channel ID to get uploader name at a later time if needed
				'channel_id'	=> $video['snippet']['channelId'],	
				'uploader_name' => '',
				'uploader' 		=> '',
				'published'		=> $video['snippet']['publishedAt'],
				'title' 		=> $video['snippet']['title'],
				'description'	=> $video['snippet']['description'],
				// category name needs to be retrieved based on category ID stored here
				'category_id'	=> $video['snippet']['categoryId'],
				'category'		=> '',
				'duration'		=> $this->_iso_to_timestamp( $video['contentDetails']['duration'] ),
				'iso_duration'	=> $video['contentDetails']['duration'],
				// store video definition (sd, hd, etc)
				'definition'	=> $video['contentDetails']['definition'],
				'thumbnails'	=> $video['snippet']['thumbnails'],
				'stats' => array(
					// rating no longer available in API V3
					'rating'		=> 0,
					'rating_count'	=> 0,
					'comments'		=> $video['statistics']['commentCount'],
					'comments_feed' => '',
					'views'			=> $video['statistics']['viewCount'],
					'likes'			=> $video['statistics']['likeCount'],
					'dislikes'		=> $video['statistics']['dislikeCount'],
					'favourite'		=> $video['statistics']['favoriteCount']
				),
				'privacy' => array(
					'status'	 => $video['status']['privacyStatus'],
					'embeddable' => $video['status']['embeddable']
				)
			);
			$categories[] = $video['snippet']['categoryId'];				
		}
		
		// query categories ids if they should be included
		if( $this->include_categories ){
			if( $categories ){
				$categories = array_unique( $categories );
				$cat = $this->get_categories( implode( ',', $categories ) );				
				if( !is_wp_error( $cat ) ){
					foreach( $videos as $key => $video ){
						if( array_key_exists( $video['category_id'] , $cat ) ){
							$videos[ $key ]['category'] = $cat[ $video['category_id'] ];
						}
					}
				}
			}
		}			
		
		return $videos;
	}
	
	/**
	 * Makes a cURL request and stores unserialized response in 
	 * $this->api_response variable
	 */
	private function _make_request( $url ){
		$response = wp_remote_get( $url );
		// if something went wrong, return the error
		if( is_wp_error( $response ) ){
			return $response;
		}
		
		// requests should be returned having code 200
		if( 200 != wp_remote_retrieve_response_code( $response ) ){
			$body 		= json_decode( wp_remote_retrieve_body( $response ), true );
			$yt_error 	= '';
			if( isset( $body['error'] ) ){
				$yt_error = $body['error']['errors'][0]['message'] . '( code : ' . $body['error']['errors'][0]['reason'] . ' ).';
			}else{
				$yt_error = 'unknown.';
			}
			
			$error = sprintf( __( 'YouTube API returned a %s error code. Error returned is: %s', 'yti_video' ), wp_remote_retrieve_response_code( $response ), $yt_error );
			return $this->_generate_error( 'yt_api_error_code', $error, $body );			
		}
		
		// decode the result
		$result = json_decode( wp_remote_retrieve_body( $response ), true );
		
		// check for empty result
		if( isset( $result['pageInfo']['totalResults'] ) ){
			if( 0 == $result['pageInfo']['totalResults'] ){
				return $this->_generate_error( 'yt_query_results_empty', __( 'Query to YouTube API returned no results.', 'yti_video' ) );
			}
		}
		if( ( isset( $result['items'] ) && !$result['items'] ) || !isset( $result['items'] ) ){
			return $this->_generate_error( 'yt_query_results_empty', __( 'Query to YouTube API returned no results.', 'yti_video' ) );
		}
					
		return $result;			
	}		
	
	/**
	 * Based on $action and $query, create the endpoint URL to 
	 * interogate YouTube API
	 */
	private function _get_endpoint( $action, $query = '', $page_token = '', $args = array() ){
		// don't allow empty queries
		if( empty( $query ) ){
			/**
			 * DO NOT USE HELPER $this->_generate_error().
			 * This isn't a response generated by YouTube, it's a plugin error that shouldn't count as
			 * YouTube error.
			 */
			return new WP_Error( 'yt_api_query_empty', __( 'No query specified.', 'yti_video' ) );
		}
		// API3 will always ask for server key, make sure it isn't empty
		if( empty( $this->server_key ) ){
			/**
			 * DO NOT USE HELPER $this->_generate_error().
			 * This isn't a response generated by YouTube, it's a plugin error that shouldn't count as
			 * YouTube error.
			 */
			return new WP_Error( 'yt_server_key_empty', __( 'You must enter your YouTube server key in plugins Settings page under tab API & License.', 'yti_video' ) );
		}
		
		$defaults = array(
			'order' 	=> 'date',
			'duration' 	=> 'any',
			'embed'		=> 'any'
		);
		extract( wp_parse_args($args, $defaults), EXTR_SKIP );
		
		$actions = array(
			// https://developers.google.com/youtube/v3/docs/search/list
			'search' => array(
				'action' => 'search',					
				'params' => array(
					'q' 			=> urlencode( $query ),
					'part' 			=> 'snippet',
					'type'			=> 'video',
					'pageToken' 	=> $page_token,
					'maxResults' 	=> $this->per_page,
					/**
					 * order param can have value:
					 * - date (newest to oldest)
					 * - rating (high to low)
					 * - relevance (default in API)
					 * - title (alphabetically by title)
					 * - viewCount (high to low)
					 */
					'order' 		=> $order,
					'videoDuration' => $duration,
					'videoEmbeddable' => $embed
				)					
			),
			// https://developers.google.com/youtube/v3/docs/playlistItems/list
			'playlist' => array(
				'action' => 'playlistItems', 
				'params' => array(
					'playlistId'	=> urlencode( $query ),
					'part' 			=> 'contentDetails',
					'pageToken' 	=> $page_token,
					'maxResults' 	=> $this->per_page,
				)						
			),
			'videos' => array(
				'action' => 'videos',
				'params' => array(
					'id' 	=> $query,
					'part' 	=> 'contentDetails,id,snippet,statistics,status'						
				)
			),
			// https://developers.google.com/youtube/v3/docs/channels/list
			'user_channel' => array(
				'action' => 'channels',
				'params' => array(
					'forUsername' 	=> urlencode( $query ),
					'part' 			=> 'contentDetails',
					'maxResults' 	=> $this->per_page,
					'page_token' 	=> ''
				)
			),
			// https://developers.google.com/youtube/v3/docs/channels/list
			'channel_id' => array(
				'action' => 'channels',
				'params' => array(
					'id' => urlencode( $query ),
					'part' 			=> 'contentDetails',
					'maxResults' 	=> $this->per_page,
					'page_token' 	=> ''
				)
			),
			// https://developers.google.com/youtube/v3/docs/videoCategories/list
			'categories' => array(
				'action' => 'videoCategories',
				'params' => array(
					'id' 	=> $query,
					'part' 	=> 'snippet'
				)
			)
		);
		
		if( array_key_exists( $action, $actions ) ){
			$yt_action 		= $actions[ $action ]['action']; 
			$params 		= $actions[ $action ]['params'];
			$params['key'] 	= $this->server_key;
			$endpoint 		= $this->base . $yt_action . '/?' . http_build_query( $params );
			return $endpoint;
		}else{
			/**
			 * DO NOT USE HELPER $this->_generate_error().
			 * This isn't a response generated by YouTube, it's a script error that shouldn't count as
			 * YouTube error.
			 */
			return new WP_Error( 'unknown_yt_api_action', sprintf( __( 'Action %s could not be found to query YouTube.', $action ), 'yti_video' ) );
		}			
	}
	
	/**
	 * Converts ISO time ( ie: PT1H30M55S ) to timestamp
	 * 
	 * @param string $iso_time - ISO time
	 * @return int - seconds
	 */
	private function _iso_to_timestamp( $iso_time ){
		preg_match_all('|([0-9]+)([a-z])|Ui', $iso_time, $matches);
		if( isset( $matches[2] ) ){
			$seconds = 0;
			foreach( $matches[2] as $key => $unit ){
				$multiply = 1;
				switch( $unit ){
					case 'M':
						$multiply = 60;
					break;	
					case 'H':
						$multiply = 3600;
					break;	
				}
				$seconds += $multiply * $matches[1][ $key ];
			}
		}
		return $seconds;
	}
	
	/**
	 * Generates and returns a WP_Error
	 * @param string $code
	 * @param string $message
	 * @param mixed $data
	 */
	private function _generate_error( $code, $message, $data = false ){		
		$error = new WP_Error( $code, $message, array( 'youtube_error' => true, 'data' => $data ) );
		return $error;		
	}
}