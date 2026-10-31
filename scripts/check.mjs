import {spawnSync} from 'node:child_process';
import {readdirSync,mkdirSync,writeFileSync} from 'node:fs';
import path from 'node:path';
const slug='multisite-manager';
const root=path.resolve('plugins',slug);
function walk(dir){return readdirSync(dir,{withFileTypes:true}).filter(e=>!e.name.startsWith('._')).flatMap(e=>e.isDirectory()?walk(path.join(dir,e.name)):[path.join(dir,e.name)]);}
const commands=walk(root).filter(f=>f.endsWith('.php')).map(f=>['run','cli','php','-l',`/var/www/html/wp-content/plugins/${slug}/`+path.relative(root,f)]);
commands.push(['run','cli','wp','plugin','is-active',slug]);
commands.push(['run','cli','php','/var/www/html/opt/msm-tests/policy.php',`/var/www/html/wp-content/plugins/${slug}`]);
commands.push(['run','cli','php','/var/www/html/opt/msm-tests/setup-policy.php',`/var/www/html/wp-content/plugins/${slug}`]);
const results=[];
for(const args of commands){
 const r=spawnSync(path.resolve('node_modules/.bin/wp-env'),args,{encoding:'utf8',timeout:60000});
 const output=(r.stdout||'')+(r.stderr||'')+(r.error?.message||'');
 console.log(output); results.push({args,exitCode:r.status,output});
}
mkdirSync('reports',{recursive:true});
writeFileSync('reports/check.json',JSON.stringify({at:new Date().toISOString(),scope:'PHP syntax, activation and isolated policy scenarios; HTTP tests separate',passed:results.every(r=>r.exitCode===0),results},null,2));
if(results.some(r=>r.exitCode!==0))process.exitCode=1;
