const test=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
function load(name,post) {
  const source=fs.readFileSync(`resources/assets/js/components/noticeboard/${name}.vue`,'utf8');
  const script=source.match(/<script>([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm,'').replace('export default','module.exports =');
  const sandbox={module:{exports:{}},VueDatePicker:{},QuillEditor:{},FormData,axios:{post},window:{location:{reload(){}}}};
  vm.runInNewContext(script,sandbox);
  const component=sandbox.module.exports;
  return Object.assign(component.data.call({p_date:'2026-10-30',e_date:'2026-10-31'}),component.methods,{title:'إعلان 8',description:'Homework <strong>report</strong>',id:1});
}
for(const [name,method] of [['Create','checkForm'],['Edit','updateCircular']]) {
  test(`${name} sends one request while pending and restores submission state`,async()=>{
    let resolve,calls=0;
    const state=load(name,()=>{calls++;return new Promise(r=>resolve=r);});
    const pending=state[method]();state[method]();
    assert.equal(calls,1);assert.equal(state.submitting,true);
    resolve({data:{success:'Saved'}});await pending;
    assert.equal(state.submitting,false);assert.equal(state.success,'Saved');
  });
  test(`${name} keeps entered data after network and validation failures`,async()=>{
    for(const error of [new Error('Network unavailable'),{response:{data:{errors:{title:['Invalid title']}}}},{response:{status:500}}]) {
      const state=load(name,()=>Promise.reject(error));
      await state[method]();
      assert.equal(state.title,'إعلان 8');assert.equal(state.description,'Homework <strong>report</strong>');
      assert.equal(state.submitting,false);assert.ok(state.errors.form || state.errors.title);
    }
  });
  test(`${name} does not accept an empty successful HTTP response`,async()=>{
    const state=load(name,()=>Promise.resolve({data:null}));await state[method]();
    assert.ok(state.errors.form);assert.equal(state.success,null);assert.equal(state.title,'إعلان 8');
  });
}
