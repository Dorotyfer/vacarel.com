const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const context=vm.createContext({definitions:{},Intl,BigInt});
vm.runInContext(fs.readFileSync('assets/reports.js','utf8'),context);
assert.equal(vm.runInContext("reportAmount('12345678901234567')",context),'12.345.678.901.234.567');
assert.equal(vm.runInContext("reportAmount('-1000.2500',true)",context),'-1.000,25');
assert.equal(vm.runInContext("reportAmount('11000')",context),'11.000');
console.log('Importes de informes sin pérdida de precisión verificados.');
