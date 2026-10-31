import {spawnSync} from 'node:child_process';
import fs from 'node:fs';
const root=process.cwd();
const php=`require_once ABSPATH . 'wp-admin/includes/network.php';
global $wpdb;
$real_db = $wpdb;
$real_user = get_current_user_id();
$observed = array('wordpress'=>get_bloginfo('version'),'php'=>PHP_VERSION,'is_multisite'=>is_multisite(),'siteurl'=>get_option('siteurl'),'active_plugins_count'=>count(get_option('active_plugins',array())),'native_subdirectories_allowed'=>allow_subdirectory_install(),'published_older_than_month'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_date < DATE_SUB(NOW(), INTERVAL 1 MONTH) AND post_status = 'publish'"),'core_setup_capability'=>'setup_network');
$checks=array();
$checks[]=array('id'=>'current_native_gate','kind'=>'live_read_only','pass'=>$observed['native_subdirectories_allowed']===($observed['published_older_than_month']===0));
$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
if($admins){wp_set_current_user((int)$admins[0]);$checks[]=array('id'=>'administrator_setup_network','kind'=>'live_capability_in_memory','pass'=>current_user_can('setup_network'));}
wp_set_current_user(0);$checks[]=array('id'=>'anonymous_setup_network_denied','kind'=>'live_capability_in_memory','pass'=>!current_user_can('setup_network'));
wp_set_current_user($real_user);
$fake=new class {public $posts='probe_posts';public $row=null;public function get_row($sql){return $this->row;}};
try {$wpdb=$fake; $checks[]=array('id'=>'no_old_published_content','kind'=>'native_function_mock_db','pass'=>allow_subdirectory_install()===true);$fake->row=(object)array('ID'=>1);$checks[]=array('id'=>'old_published_content_blocks','kind'=>'native_function_mock_db','pass'=>allow_subdirectory_install()===false);add_filter('allow_subdirectory_install','__return_true');$checks[]=array('id'=>'filter_can_override_gate','kind'=>'native_function_mock_db','pass'=>allow_subdirectory_install()===true);remove_filter('allow_subdirectory_install','__return_true');}finally{$wpdb=$real_db;}
echo wp_json_encode(array('observed'=>$observed,'checks'=>$checks,'pass'=>!in_array(false,array_column($checks,'pass'),true)),JSON_PRETTY_PRINT);`;
const r=spawnSync(root+'/node_modules/.bin/wp-env',['run','cli','wp','eval',php],{cwd:root,encoding:'utf8',timeout:45000});
const out=r.stdout||'';const start=out.indexOf('{');const end=out.lastIndexOf('}');
if(r.status!==0||start<0){console.error(out,r.stderr);process.exit(1);}
const report=JSON.parse(out.slice(start,end+1));
report.scope='Read-only inspection and isolated in-memory native-function probes. No conversion or site creation performed; no PHP matrix certified.';
fs.writeFileSync(root+'/reports/feasibility-probe.json',JSON.stringify(report,null,2)+'\n');
console.log(JSON.stringify(report,null,2));
if(!report.pass)process.exitCode=1;
