import fs from 'node:fs';
import crypto from 'node:crypto';
import {spawnSync} from 'node:child_process';
import path from 'node:path';
const origin='http://localhost:8899';
const report=[];
function expect(name,ok){report.push({name,passed:!!ok});if(!ok)throw Error(name);}
function session(){
 const jar=new Map();
 return async function request(url,options={}){
   const r=await fetch(origin+url,{...options,redirect:'manual',signal:AbortSignal.timeout(20000),headers:{...options.headers,Cookie:[...jar].map(([k,v])=>`${k}=${v}`).join('; ')}});
   for(const cookie of r.headers.getSetCookie()){const first=cookie.split(';')[0],i=first.indexOf('=');jar.set(first.slice(0,i),first.slice(i+1));}
   const text=await r.text();return {status:r.status,headers:r.headers,text};
 };
}
async function login(request,user,password){
 await request('/wp-login.php');
 const body=new URLSearchParams({log:user,pwd:password,'wp-submit':'Log In',redirect_to:origin+'/wp-admin/',testcookie:'1'});
 const r=await request('/wp-login.php',{method:'POST',body});
 if(r.status!==302)throw Error('Login fixture failed');
}
function wp(code){
 const r=spawnSync(path.resolve('node_modules/.bin/wp-env'),['run','tests-cli','wp','eval',code],{encoding:'utf8',timeout:60000});
 if(r.status!==0)throw Error('WordPress fixture command failed (output withheld to protect fixture credentials)');
 return r.stdout;
}
let fixtureId=null;
try{
 const anonymous=session();
 const anon=await anonymous('/wp-admin/admin.php?page=multisite-manager');
 expect('anonymous dashboard requires authentication',anon.status===302 && (anon.headers.get('location')||'').includes('wp-login'));
 const anonExport=await anonymous('/wp-admin/admin-post.php',{method:'POST',body:new URLSearchParams({action:'msm_export_guide'})});
 expect('anonymous export denied',anonExport.status!==200 || !anonExport.headers.get('content-disposition'));
 const admin=session();await login(admin,'admin','password');
 const page=await admin('/wp-admin/admin.php?page=multisite-manager');
 expect('administrator dashboard loads',page.status===200 && page.text.includes('Multisite Manager') && page.text.includes('msm_export_guide'));
 const nonce=page.text.match(/name="msm_nonce"[^>]*value="([^"]+)"/)?.[1];
 expect('export includes nonce',!!nonce);
 const bad=await admin('/wp-admin/admin-post.php',{method:'POST',body:new URLSearchParams({action:'msm_export_guide',msm_nonce:'invalid'})});
 expect('invalid nonce denied',bad.status===403 && !bad.headers.get('content-disposition'));
 const missing=await admin('/wp-admin/admin-post.php',{method:'POST',body:new URLSearchParams({action:'msm_export_guide'})});
 expect('missing nonce denied',missing.status===403 && !missing.headers.get('content-disposition'));
 const wrongMethod=await admin('/wp-admin/admin-post.php?action=msm_export_guide&msm_nonce='+encodeURIComponent(nonce));
 expect('GET export rejected',wrongMethod.status===405 || wrongMethod.status===403);
 const good=await admin('/wp-admin/admin-post.php',{method:'POST',body:new URLSearchParams({action:'msm_export_guide',msm_nonce:nonce})});
 expect('authorized guide download',good.status===200 && (good.headers.get('content-disposition')||'').includes('attachment') && good.text.length>500);
 expect('guide excludes config secrets and absolute host paths',!/(DB_PASSWORD|AUTH_KEY|SECURE_AUTH_KEY|\/Users\/|\/Volumes\/|\/var\/www\/)/.test(good.text));
 expect('guide has real instructions',/backup/i.test(good.text) && /hosting/i.test(good.text));
 const user='msm_qa_'+crypto.randomBytes(5).toString('hex');const password=crypto.randomBytes(24).toString('hex');
 const output=wp(`$id=wp_insert_user(array('user_login'=>'${user}','user_pass'=>'${password}','role'=>'subscriber'));if(is_wp_error($id)){throw new Exception('fixture failed');}echo 'MSM_ID='.$id;`);
 fixtureId=Number(output.match(/MSM_ID=(\d+)/)?.[1]);if(!fixtureId)throw Error('Missing fixture id');
 const subscriber=session();await login(subscriber,user,password);
 const denied=await subscriber('/wp-admin/admin.php?page=multisite-manager');
 expect('subscriber dashboard denied',denied.status===403);
 const deniedExport=await subscriber('/wp-admin/admin-post.php',{method:'POST',body:new URLSearchParams({action:'msm_export_guide',msm_nonce:nonce})});
 expect('subscriber export denied',deniedExport.status===403 && !deniedExport.headers.get('content-disposition'));
} catch(error){console.error(error.message);process.exitCode=1;}
finally{
 if(fixtureId){try{wp(`require_once ABSPATH.'wp-admin/includes/user.php';if(!wp_delete_user(${fixtureId})){throw new Exception('fixture cleanup failed');}`);report.push({name:'temporary subscriber removed',passed:true});}catch(e){report.push({name:'temporary subscriber removed',passed:false});process.exitCode=1;}}
 fs.mkdirSync('reports',{recursive:true});fs.writeFileSync('reports/http-check.json',JSON.stringify({at:new Date().toISOString(),origin,scope:'Live single-site HTTP authorization, nonce and export; disposable subscriber fixture',passed:!process.exitCode,results:report},null,2));
 console.log(JSON.stringify(report,null,2));
}
