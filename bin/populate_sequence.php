<?php
$midgard = new midgard_connection();
$midgard->open('midgard');

@ini_set('max_execution_time', 0);

// Figure out when sequence was updated last time
$qb = new midgard_query_builder('midgard_sequence');
$qb->set_limit(1);
$qb->add_order('id', 'DESC');
$transactions = $qb->execute();
if (count($transactions) == 0)
{
    $latest_sequence = new midgard_datetime('0000-00-00');
}
else
{
    foreach ($transactions as $transaction)
    {
        $latest_sequence = $transaction->metadata->created;
    }
}

$sequence = array();
foreach ($_MIDGARD['schema']['types'] as $type => $val)
{
    if (substr($type, 0, 2) == '__')
    {
        continue;
    }
    
    // Updated objects
    $qb = new midgard_query_builder($type);
    $qb->add_constraint('metadata_revised', '>', $latest_sequence);
    $qb->add_order('metadata_revised', 'ASC');
    $objects = $qb->execute();
    foreach ($objects as $object)
    {
        $revised = $object->metadata->revised->format(DATE_ATOM);
        $sequence["{$revised}_{$object->guid}"] = array
        (
            'objectguid' => $object->guid,
            'type' => $type,
            'revision' => $object->metadata->revision,
        );
    }
}

ksort($sequence);

// Populate the sequence to DB
foreach ($sequence as $seq)
{
    $seq_object = new midgard_sequence();
    $seq_object->objectguid = $seq['objectguid'];
    $seq_object->type = $seq['type'];
    $seq_object->revision = $seq['revision'];
    $seq_object->create();
}
?>