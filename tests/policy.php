<?php
// Policy tests independent of WordPress. Live endpoint checks are separate.
define('ABSPATH', __DIR__ . '/');
function __($text, $domain = '') { return $text; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function untrailingslashit($text) { return rtrim($text, '/\\'); }
require $argv[1] . '/includes/class-policy.php';
$base = array('wp_version'=>'7.1.4','php_version'=>'8.3.36','db_version'=>'11.8','multisite'=>false,'subdomain_install'=>false,'network_count'=>0,'custom_topology'=>false,'topology_verified'=>true,'site_url'=>'https://example.com','home_url'=>'https://example.com','https'=>true,'authorized'=>true,'old_published_count'=>0,'native_subdirectory_allowed'=>true,'network_db_state'=>'none','active_plugins_count'=>1,'wp_allow_multisite'=>false,'do_not_upgrade_global_tables'=>false,'config_location'=>'root','config_readable'=>true,'config_writable'=>true,'custom_content_dir'=>false,'server'=>'apache','admin_count'=>1,'permalink_structure'=>'/%postname%/');
$results = array();
function check_case($name, $changes, $id, $expected) {
 global $base, $results;
 $facts = array_merge($base, $changes); $before = $facts;
 $report = \MSM\Policy::evaluate($facts);
 $actual = null;
 if ($id === 'overall') { $actual = $report['overall']['status']; }
 else { foreach ($report['checks'] as $row) { if ($row['id'] === $id) { $actual=$row['status']; break; } } }
 $results[] = array('name'=>$name,'expected'=>$expected,'actual'=>$actual,'pass'=>$actual===$expected && $facts===$before);
}
check_case('healthy profile does not imply hosting approval',array(),'overall','unknown');
check_case('WordPress below minimum',array('wp_version'=>'6.9.1'),'overall','unsupported');
check_case('future WordPress is not certified',array('wp_version'=>'8.0.0'),'wordpress','unknown');
check_case('PHP below minimum',array('php_version'=>'7.3.33'),'overall','unsupported');
check_case('PHP beyond target',array('php_version'=>'8.6.0'),'php','unsupported');
check_case('unauthorized actor',array('authorized'=>false),'overall','unsupported');
check_case('old content requires plan even when override allows',array('old_published_count'=>2,'native_subdirectory_allowed'=>true),'published_content','action');
check_case('database error is not zero content',array('old_published_count'=>null),'published_content','unknown');
check_case('unknown native gate',array('native_subdirectory_allowed'=>null),'published_content','unknown');
check_case('partial network needs recovery',array('network_db_state'=>'present'),'network_state','action');
check_case('unreadable config',array('config_readable'=>false),'configuration','unknown');
check_case('nonwritable config',array('config_writable'=>false),'configuration','action');
check_case('global-table blocker',array('do_not_upgrade_global_tables'=>true),'overall','unsupported');
check_case('subdomain network excluded',array('multisite'=>true,'subdomain_install'=>true,'network_count'=>1),'overall','unsupported');
check_case('multiple networks excluded',array('multisite'=>true,'network_count'=>2),'topology','unsupported');
check_case('custom topology excluded',array('multisite'=>true,'network_count'=>1,'custom_topology'=>true),'topology','unsupported');
check_case('unknown network count',array('network_count'=>null),'topology','unknown');
check_case('custom local port stays unverified',array('site_url'=>'http://localhost:8898','home_url'=>'http://localhost:8898'),'urls','unknown');
check_case('different site/home paths',array('site_url'=>'https://example.com/wp'),'urls','action');
check_case('Nginx rewrite remains unverified',array('server'=>'nginx'),'rewrite','unknown');
foreach(array('7.4.33','8.0.30','8.1.0','8.2.0','8.3.0','8.4.0','8.5.0') as $v) {check_case('PHP target '.$v,array('php_version'=>$v),'php','pass');}
$network=\MSM\Policy::evaluate(array_merge($base,array('multisite'=>true,'network_count'=>1)));
$ids=array_column($network['checks'],'id');
$results[]=array('name'=>'existing network does not request new setup','pass'=>!in_array('enable_network',$ids,true) && !in_array('published_content',$ids,true));
foreach (array(array(), array_fill_keys(array_keys($base), null)) as $empty) {
 $r=\MSM\Policy::evaluate($empty);
 $results[]=array('name'=>'missing or null observations never pass','pass'=>count(array_unique(array_column($r['checks'],'status')))===1 && $r['checks'][0]['status']==='unknown');
}
$passed=!in_array(false,array_column($results,'pass'),true);
echo json_encode(array('scope'=>'isolated policy scenarios with WordPress helpers stubbed; not full WordPress compatibility','php'=>PHP_VERSION,'count'=>count($results),'passed'=>$passed,'results'=>$results),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
exit($passed?0:1);
