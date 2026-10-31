import fs from 'node:fs';
import {spawnSync} from 'node:child_process';
const root=process.cwd();
const images=['php:7.4-cli','php:8.0-cli','php:8.1-cli','php:8.2-cli','php:8.3-cli','php:8.4-cli','wordpress:cli-php8.5'];
const results=[];
for(const image of images){
 const base=['run','--rm','--network','none','--entrypoint','php','-v',root+'/plugins/multisite-manager:/plugin:ro','-v',root+'/tests:/tests:ro',image];
 const test=spawnSync('docker',[...base,'/tests/policy.php','/plugin'],{encoding:'utf8',timeout:120000});
 if(test.status!==0){console.error(test.stdout,test.stderr);process.exit(1);}
 const result=JSON.parse(test.stdout);
 const setup=spawnSync('docker',[...base,'/tests/setup-policy.php','/plugin'],{encoding:'utf8',timeout:120000});
 const lint=spawnSync('docker',[...base,'-r',`$ok=true;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/plugin',FilesystemIterator::SKIP_DOTS)) as $f){if($f->getExtension()==='php' && substr($f->getFilename(),0,2)!=='._'){passthru(PHP_BINARY.' -l '.escapeshellarg($f->getPathname()),$code);if($code!==0)$ok=false;}}exit($ok?0:1);`],{encoding:'utf8',timeout:120000});
 results.push({image,php:result.php,policyTests:result.count,setupTests:setup.stdout.trim(),passed:result.passed && lint.status===0 && setup.status===0,lint:lint.stdout+lint.stderr});
 console.log(result.php+': '+(results.at(-1).passed?'PASS':'FAIL'));
}
fs.writeFileSync('reports/php-matrix.json',JSON.stringify({scope:'Syntax and isolated policy tests; not full WordPress integration matrix',results},null,2));
if(results.some(r=>!r.passed))process.exitCode=1;
