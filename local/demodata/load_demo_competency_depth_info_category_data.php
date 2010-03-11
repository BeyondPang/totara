<?php
@raise_memory_limit('392M');
@ini_set('max_execution_time','3000');
print "Loading data for table 'competency_depth_info_category'<br>";
$items = array(array('id' => '1','name' => 'Miscellaneous','sortorder' => '1','depthid' => '4',),
array('id' => '2','name' => 'Miscellaneous','sortorder' => '1','depthid' => '5',),
array('id' => '3','name' => 'Miscellaneous','sortorder' => '1','depthid' => '6',),
array('id' => '4','name' => 'primary category','sortorder' => '1','depthid' => '1',),
array('id' => '5','name' => 'secondary category','sortorder' => '2','depthid' => '1',),
);
print "\n";print "Inserting ".count($items)." records<br />\n";
$i=1;
foreach($items as $item) {
    if(get_field('competency_depth_info_category', 'id', 'id', $item['id'])) {
        print "Record with id of {$item['id']} already exists!<br>\n";
        continue;
    }
    $newid = insert_record('competency_depth_info_category',(object) $item);
    if($newid != $item['id']) {
        if(!set_field('competency_depth_info_category', 'id', $item['id'], 'id', $newid)) {
            print "Could not change id from $newid to {$item['id']}<br>\n";
            continue;
        }
    }
    // record the highest id in the table
    $maxid = get_field_sql('SELECT '.sql_max('id').' FROM '.$CFG->prefix.'competency_depth_info_category');
    // make sure sequence is higher than highest ID
    bump_sequence('competency_depth_info_category', $CFG->prefix, $maxid);
    // print output
    // 1 dot per 10 inserts
    if($i%10==0) {
        print ".";
        flush();
    }
    // new line every 200 dots
    if($i%2000==0) {
        print $i." <br>";
    }
    $i++;
}
print "<br>";
