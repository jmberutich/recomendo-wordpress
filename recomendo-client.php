<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// class that handles all communications to Recomendo servers
class Recomendo_Client {

    const DATE_TIME_FORMAT = DateTime::ISO8601;
    const TIMEOUT = 3;
    const HTTPVERSION = '1.1';
    const AUTH_URL = 'https://auth.recomendo.ai/v1/auth';
    const EVENTS_URL = 'https://events.recomendo.ai/v1/events';
    const PREDICTIONS_URL = 'https://predictions.recomendo.ai/v1/queries.json';



    function __construct() {

        $options = get_option( 'recomendo_api' );

        $this->client_id = $options['client_id'];
        $this->client_secret = $options['client_secret'];

        $this->recomendo_version = Recomendo_Admin::get_version();

        global $wp_version;
        $this->user_agent = 'WordPress/' . $wp_version . ' - ' .
                            'Recomendo/' . $this->recomendo_version;

    }

    public function get_header() {
        return array(
                'Content-Type' => 'application/json',
                'User-Agent' =>  $this->user_agent,
                'Accept-Encoding' => 'gzip',
                'Authorization' => 'Bearer ' . $this->get_token()
        );
    }



    public function get_token( $client_id=false, $client_secret=false ) {

        if (! $client_id )
            $client_id = $this->client_id;

        if (! $client_secret )
            $client_secret = $this->client_secret;

        if ( false === ( $token = get_transient( 'recomendo_token' ) ) ) {
            $base_auth = base64_encode( $client_id . ':' . $client_secret );
            $headers = array( 'Authorization' => 'Basic ' . $base_auth );
            $response = wp_remote_post( self::AUTH_URL,
                            array(
                              'timeout' => self::TIMEOUT,
                              'httpversion' => self::HTTPVERSION,
                              'headers' => $headers
                            )
                        );

            if ( ! is_wp_error( $response ) ) {
                $body = json_decode( $response['body'] );
                $token = $body->payload->token;
                set_transient( 'recomendo_token', $token , 3500 );
            }
        }

        return $token;
    }



    private function get_event_time($event_time) {
      $result = $event_time;
      if (!isset($event_time)) {
        $result = (new DateTime('NOW'))->format(self::DATE_TIME_FORMAT);
      }
      return $result;
    }

    /**
     * Send prediction query to an Engine Instance
     *
     * @param array Query
     *
     * @return array JSON response
     *
     */
    public function send_query( array $query ) {
        $url = self::PREDICTIONS_URL;

        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header(),
                          'body' => json_encode( $query ),
                        )
                    );

        return $response;
    }

    /**
     * Set a user entity
     *
     * @param int|string User Id
     * @param array Properties of the user entity to set
     * @param string Time of the event in ISO 8601 format
     *               (e.g. 2014-09-09T16:17:42.937-08:00).
     *               Default is the current time.
     *
     * @return string JSON response
     *
     */
    public function set_user( $uid, array $properties=array(), $event_time=null ) {
        $event_time = $this->get_event_time($event_time);
        // casting to object so that an empty array would be represented as {}
        if (empty($properties)) $properties = (object)$properties;

        $url = self::EVENTS_URL . '.json';

        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header(),
                          'body' => json_encode([
                              'event' => '$set',
                              'entityType' => 'user',
                              'entityId' => $uid,
                              'properties' => $properties,
                              'eventTime' => $event_time,
                           ])
                         )
                    );


        return $response;
    }

    /**
     * Unset a user entity
     *
     * @param int|string User Id
     * @param array Properties of the user entity to unset
     * @param string Time of the event in ISO 8601 format
     *               (e.g. 2014-09-09T16:17:42.937-08:00).
     *               Default is the current time.
     *
     * @return string raw response
     *
     */
    public function unset_user( $uid, array $properties, $event_time=null ) {

        if (empty($properties))
            return new WP_Error( 'unset_user', 'Specify at least one property' );

        $url = self::EVENTS_URL . '.json';
        $event_time = $this->get_event_time($event_time);

        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header(),
                          'body' => json_encode([
                              'event' => '$unset',
                              'entityType' => 'user',
                              'entityId' => $uid,
                              'properties' => $properties,
                              'eventTime' => $event_time,
                          ])
                        )
                    );

        return $response;
    }

    /**
     * Delete a user entity
     *
     * @param int|string User Id
     * @param string Time of the event in ISO 8601 format
     *               (e.g. 2014-09-09T16:17:42.937-08:00).
     *               Default is the current time.
     *
     * @return string raw response
     *
     */
    public function delete_user( $uid, $event_time=null ){
        $event_time = $this->get_event_time($event_time);
        $url = self::EVENTS_URL . '.json';
        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header(),
                          'body' => json_encode([
                              'event' => '$delete',
                              'entityType' => 'user',
                              'entityId' => $uid,
                              'eventTime' => $event_time,
                          ])
                        )
                    );
        return $response;
    }

    /**
     * Set an item entity
     *
     * @param int|string Item Id
     * @param array Properties of the item entity to set
     * @param string Time of the event in ISO 8601 format
     *               (e.g. 2014-09-09T16:17:42.937-08:00).
     *               Default is the current time.
     *
     * @return string JSON response
     *
     */
    public function set_item( $iid, array $properties=array(), $event_time=null ) {
        $event_time = $this->get_event_time($event_time);
        if (empty($properties)) $properties = (object)$properties;
        $url = self::EVENTS_URL . '.json';
        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header(),
                          'body' => json_encode([
                              'event' => '$set',
                              'entityType' => 'item',
                              'entityId' => $iid,
                              'properties' => $properties,
                              'eventTime' => $event_time,
                          ])
                        )
                    );
        return $response;

    }

    /**
     * Unset an item entity
     *
     * @param int|string Item Id
     * @param array Properties of the item entity to unset
     * @param string Time of the event in ISO 8601 format
     *               (e.g. 2014-09-09T16:17:42.937-08:00).
     *               Default is the current time.
     *
     * @return string JSON response
     *
     */
    public function unset_item( $iid, array $properties, $event_time=null ) {

        if (empty($properties))
            return new WP_Error( 'unset_item', 'Specify at least one property' );

        $url = self::EVENTS_URL . '.json';
        $event_time = $this->get_event_time($event_time);

        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header(),
                          'body' => json_encode([
                              'event' => '$unset',
                              'entityType' => 'item',
                              'entityId' => $iid,
                              'properties' => $properties,
                              'eventTime' => $event_time,
                          ])
                        )
                    );
        return $response;

    }
    /**
     * Delete an item entity
     *
     * @param int|string Item Id
     * @param string Time of the event in ISO 8601 format
     *               (e.g. 2014-09-09T16:17:42.937-08:00).
     *               Default is the current time.
     *
     * @return string RAW response
     *
     */
    public function delete_item( $iid, $event_time=null ) {
        $event_time = $this->get_event_time($event_time);
        $url = self::EVENTS_URL . '.json';
        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header(),
                          'body' => json_encode([
                              'event' => '$delete',
                              'entityType' => 'item',
                              'entityId' => $iid,
                              'eventTime' => $event_time,
                          ])
                        )
                    );
        return $response;
    }

    /**
     * Record a user action on an item
     *
     * @param string Event name
     * @param int|string User Id
     * @param int|string Item Id
     * @param array Properties of the event
     * @param string Time of the event in ISO 8601 format
     *               (e.g. 2014-09-09T16:17:42.937-08:00).
     *               Default is the current time.
     *
     * @return string RAW response
     *
     */
    public function record_user_action( $event, $uid, $iid,
                                 array $properties=array(), $event_time=null ) {

        $event_time = $this->get_event_time($event_time);
        if (empty($properties)) $properties = (object)$properties;

        $url = self::EVENTS_URL . '.json';

        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header(),
                          'body' => json_encode([
                              'event' => $event,
                              'entityType' => 'user',
                              'entityId' => $uid,
                              'targetEntityType' => 'item',
                              'targetEntityId' => $iid,
                              'properties' => $properties,
                              'eventTime' => $event_time,
                          ])
                        )
                    );

        return  $response;
    }

    /**
     * Create an event
     *
     * @param array An array describing the event
     *
     * @return string RAW response
     *
     */
    public function create_event(array $data) {
        $url = self::EVENTS_URL . '.json';

        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header(),
                          'body' => json_encode($data)
                        )
                    );

        return $response;
    }

    /**
     * Retrieve an event
     *
     * @param string Event ID
     *
     * @return string RAW response
     *
     */
    public function get_event($event_id) {
        $url = self::EVENTS_URL . "/$event_id.json";

        $response = wp_remote_get( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header()
                        )
                    );

        return $response;
    }

    // startTime: time in ISO8601 format. Return events with eventTime >= startTime.
    // untilTime: time in ISO8601 format. Return events with eventTime < untilTime.
    // entityType: String. The entityType. Return events for this entityType only.
    // entityId: String. The entityId. Return events for this entityId only.
    // event: String. The event name. Return events with this name only.
    // targetEntityType: String. The targetEntityType. Return events for this targetEntityType only.
    // targetEntityId: String. The targetEntityId. Return events for this targetEntityId only.
    // limit: Integer. The number of record events returned. Default is 20. -1 to get all.
    // reversed: Boolean. Must be used with both entityType and entityId specified, returns events in reversed chronological order. Default is false.
    public function get_events( $start_time = null, $until_time = null, string $entity_type=null,
                                string $entity_id=null, string $event=null, string $target_entity_type=null,
                                $target_entity_id=null, int $limit=null, string $reversed=null) {

        $url = self::EVENTS_URL . ".json";

        $query_params = array();

        if ( !is_null( $start_time ) )
            $query_params['startTime'] = $start_time;

        if ( !is_null( $until_time ) )
            $query_params['untilTime'] = $until_time;

        if ( !is_null( $entity_type ) )
            $query_params['entityType'] = $entity_type;

        if ( !is_null( $entity_id ) )
            $query_params['entityId'] = $entity_id;

        if ( !is_null( $event ) )
            $query_params['event'] = $event;

        if ( !is_null( $target_entity_type ) )
            $query_params['targetEntityType'] = $target_entity_type;

        if ( !is_null( $target_entity_id ) )
            $query_params['targetEntityId'] = $target_entity_id;

        if ( !is_null( $limit ) )
            $query_params['limit'] = $limit;

        if ( !is_null( $reversed ) )
            $query_params['reversed'] = $reversed;

        if (! empty( $query_params) )
            $url .= '?' . http_build_query( $query_params );


        $response = wp_remote_get( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header()
                        )
                    );

        return $response;

    }


    // deletes an event by id
    public function delete_event( string $event_id ) {
        $url = self::EVENTS_URL . "/$event_id.json";

        $response = wp_remote_request( $url,
            array(
                'method' => 'DELETE',
                'timeout' => self::TIMEOUT,
                'httpversion' => self::HTTPVERSION,
                'headers' => $this->get_header()
            )
        );

        return $response;

    }

    /**
     * Send model training request
     *
     * @return string RAW response
     *
     */
    public function send_train_request() {

        if ( ! get_transient( 'recomendo_train_request_sent' ) )  {

            //  to prevent race condition, 
            set_transient( 'recomendo_train_request_sent', true, 1800 );

            $url = 'https://client.recomendo.ai/v1/train';

            $response = wp_remote_post( $url,
                                       array(
                                           'timeout' => self::TIMEOUT,
                                           'httpversion' => self::HTTPVERSION,
                                           'headers' => $this->get_header()
                                        )
                                      );

            // delete the transient if train request fails
            if ( is_wp_error( $response )) {
                delete_transient( 'recomendo_train_request_sent' );
            }

        } else {
            // if transient exists we ignore sending train request, but record it as OK
            $response = array( 'response' => array( 'code' => 200, 'message' => 'OK' ) );
        }

        // Here we return the raw response!
        return $response;

    }


    public static function get_json( $response ) {
        return json_decode( wp_remote_retrieve_body( $response ), true);
    }


    public function get_event_server_status() {
        $url = self::EVENTS_URL . '.json';

        $response = wp_remote_get( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header()
                        )
                    );

      return $response;
    }

    public function get_prediction_server_status() {
        $url = self::PREDICTIONS_URL;
        $response = wp_remote_post( $url,
                        array(
                          'timeout' => self::TIMEOUT,
                          'httpversion' => self::HTTPVERSION,
                          'headers' => $this->get_header()
                        )
                    );
        return $response;
    }



} // end of class Recomendo_Client
