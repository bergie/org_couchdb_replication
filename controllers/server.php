<?php
/**
 * @package org_couchdb_replication
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * CouchDb replication server
 *
 * @package org_couchdb_replication
 */
class org_couchdb_replication_controllers_server
{
    private $user = null;

    public function __construct(midcom_core_component_interface $instance)
    {
        $this->configuration = $instance->configuration;
    }

    static function get_rev($object)
    {
        return $object->metadata->revision . '-' . $object->metadata->revised->format('U');
    }

    /**
     * Handle user authentication. If session is present we use that, otherwise we do Basic authentication.
     */
    public function authenticate()
    {
        if ($_MIDCOM->authentication->is_user())
        {
            // User already has a session open
            $this->user = $_MIDCOM->authentication->get_person();
        }
        else
        {
            // We use Basic authentication
            $basic_auth = new midcom_core_services_authentication_basic();
            $e = new Exception("API usage requires Basic authentication");
            $basic_auth->handle_exception($e);
            $this->user = $basic_auth->get_person();
        }
    }

    /**
     * Get simulated "CouchDb welcome information" document
     */
    public function get_welcome(array $args)
    {
        $this->data['couchdb'] = 'Welcome';
        $this->data['version'] = '0.9.0';
    }

    /**
     * Get simulated "CouchDb database information" document
     */
    public function get_database(array $args)
    {
        if (strpos($_SERVER['REQUEST_URI'], '_all_docs_by_seq') !== false)
        {
            // Work around the bug in request_config->get_argv();
            return $this->get_docs_by_seq(array());
        }
        
        $this->data['db_name'] = 'couchdb';

        $this->data['update_seq'] = 0;        
        $qb = new midgard_query_builder('midgard_sequence');
        $qb->set_limit(1);
        $qb->add_order('id', 'DESC');
        $transactions = $qb->execute();
        foreach ($transactions as $transaction)
        {
            $this->data['update_seq'] = $transaction->id;
        }
        
        // TODO: "doc_count":1,"doc_del_count":0,"purge_seq":0,"compact_running":false,"disk_size":16422,"instance_start_time":"1246889082754247"
    }

    /**
     * Get document list for requested sequences
     */
    public function get_docs_by_seq(array $args)
    {
        //$this->authenticate();
        
        // List the requested sequence data
        
        $qb = new midgard_query_builder('midgard_sequence');
        if (isset($_MIDCOM->dispatcher->get['startkey']))
        {
            $qb->add_constraint('id', '>', (int) $_MIDCOM->dispatcher->get['startkey']);
        }

        if (isset($_MIDCOM->dispatcher->get['limit']))
        {
            $qb->set_limit((int) $_MIDCOM->dispatcher->get['limit']);
        }

        $qb->add_order('id', 'ASC');
        
        $transactions = $qb->execute();
        
        // Prepare the data list
        $this->data['total_rows'] = count($transactions);
        $this->data['offset'] = 0;
        $this->data['rows'] = array();
        
        // Map the transaction data to what CouchDb protocol wants
        foreach ($transactions as $transaction)
        {
            $this->data['rows'][] = array
            (
                'id' => $transaction->objectguid,
                'key' => $transaction->id,
                'value' => array
                (
                    'rev' => $transaction->revision . '-' . $transaction->metadata->revised->format('U'),
                ),
            );
        }
    }
    
    public function get_document(array $args)
    {
        $object = midgard_object_class::get_object_by_guid($args['guid']);
        if (   !$object
            || !$object->guid)
        {
            throw new midcom_exception_notfound("Object {$args['guid']} not found");
        }
        
        // Map the object to CoudbDb format
        $status = array();
        
        // CouchDb-specific metadata
        $status['_id'] = $object->guid;
        $status['_rev'] = org_couchdb_replication_controllers_server::get_rev($object);
        
        // Add normal object properties
        $status = array_merge($status, get_object_vars($object));

        // Remove data we shouldn't send
        unset($status['id']);
        unset($status['guid']);
        unset($status['metadata']);
        
        foreach ($status as $key => $val)
        {
            if (is_a($val, 'midgard_datetime'))
            {
                $status[$key] = $val->format(DATE_ATOM);
            }
        }
        
        $this->data[]['ok'] = $status;
    }
    
    public function post_ensure_full_commit(array $args)
    {
        $this->data[]['ok'] = true;
    }
}
?>