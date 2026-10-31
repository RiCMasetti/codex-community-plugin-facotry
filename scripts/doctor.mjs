import {spawnSync} from 'node:child_process';
let failed=false;
for(const [cmd,args] of [['git',['--version']],['node',['--version']],['npm',['--version']],['docker',['info','--format','{{.ServerVersion}}']],['docker',['compose','version']]]) {
 const r=spawnSync(cmd,args,{encoding:'utf8'});
 console.log(cmd+': '+(r.stdout?.trim()||r.stderr?.trim()||r.error?.message||'non disponibile'));
 if(r.status!==0) failed=true;
}
process.exitCode=failed?1:0;
