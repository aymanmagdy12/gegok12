const { chromium } = require('playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');

(async () => {
  const browser = await chromium.launch({headless:true});
  const report = [];
  fs.mkdirSync('tests/browser/artifacts', {recursive:true});
  try {
    for (const width of [390, 768, 1440]) {
      for (const [id, role] of [[2,'Teacher'],[4,'Parent'],[3,'Student']]) {
        const context = await browser.newContext({viewport:{width,height:900}});
        const page = await context.newPage();
        const row = {width,role,consoleErrors:[],pageErrors:[],failedRequests:[]};
        report.push(row);
        page.on('console', m => {if(m.type()==='error') row.consoleErrors.push(m.text());});
        page.on('pageerror', e => row.pageErrors.push(e.stack || e.message));
        page.on('response', r => {if(r.status()>=400 && r.url().startsWith('http://127.0.0.1:8000')) row.failedRequests.push({url:r.url(),status:r.status()});});
        // External services are not exercised with fixture data.
        await page.route('**/*', route => route.request().url().startsWith('http://127.0.0.1:8000') ? route.continue() : route.abort());
        await page.goto('http://127.0.0.1:8000/login');
        await page.locator('input[name="email"]').fill(`user${id}@example.test`);
        await page.locator('input[name="password"]').fill('IsolatedBrowserTest123!');
        await Promise.all([page.waitForURL('**/learning/academy/dashboard'),page.locator('form[aria-label="Login"] button[type="submit"]').click()]);
        await page.getByRole('heading',{name:`${role} dashboard`,exact:true}).waitFor();
        assert.equal(await page.locator('select[name="student"]').count(),0);
        if (role==='Teacher') {
          await page.getByRole('link',{name:'Open eight-session checklist'}).click();
          await page.getByRole('heading',{name:'Eight-session attendance & homework'}).waitFor();
          const open=page.getByRole('button',{name:'Open the eight-session checklist'});
          if(await open.count()) await open.click();
          const student = page.locator('details').filter({has:page.locator('summary').filter({hasText:'Account 3'})}).first();
          await student.locator('summary').click();
          await student.locator('select[name="marks[1][attendance]"]').selectOption('1');
          await student.locator('select[name="marks[1][homework]"]').selectOption('1');
          await student.getByRole('button',{name:"Save this student's eight sessions"}).click();
          await page.getByText('Eight-session attendance and homework saved.',{exact:false}).waitFor();
        } else {
          await page.getByText('12.5%',{exact:false}).first().waitFor();
          if(role==='Parent') {
            assert.ok(await page.getByRole('link',{name:'Recorded sessions',exact:false}).count()>=2);
          }
        }
        await page.screenshot({path:`tests/browser/artifacts/${role.toLowerCase()}-${width}.png`,fullPage:true});
        await page.goto('http://127.0.0.1:8000/learning/academy/notices');
        await page.getByRole('heading',{name:'Notice board',exact:true}).waitFor();
        await page.locator('input[name="q"]').fill('إعلان');
        await page.getByRole('button',{name:'Search',exact:true}).click();
        await page.getByRole('heading',{name:'إعلان الأكاديمية',exact:true}).waitFor();
        const wrong=await context.request.get('http://127.0.0.1:8000/learning/academy/not-a-real-page');
        assert.equal(wrong.status(),404);
        row.result='pass';
        await context.close();
      }
    }
  } finally {
    fs.writeFileSync('tests/browser/artifacts/report.json',JSON.stringify(report,null,2));
    console.log(JSON.stringify(report.map(({role,width,result,consoleErrors,pageErrors,failedRequests})=>({role,width,result,consoleErrors:consoleErrors.length,pageErrors:pageErrors.length,failedRequests:failedRequests.length}))));
    await browser.close();
  }
})().catch(e=>{console.error(e);process.exitCode=1;});
