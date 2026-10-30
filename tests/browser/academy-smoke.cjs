const { chromium } = require('playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');

(async () => {
  const browser = await chromium.launch({headless:true});
  const report = [];
  fs.mkdirSync('tests/browser/artifacts', {recursive:true});
  try {
    for (const width of [390, 768, 1440]) {
      for (const [id, role] of [[2,'Teacher'],[4,'Parent'],[3,'Student'],[1,'Admin']]) {
        const context = await browser.newContext({viewport:{width,height:900}});
        const page = await context.newPage();
        const row = {width,role,consoleErrors:[],pageErrors:[],failedRequests:[],blockedExternalRequests:[]};
        report.push(row);
        page.on('console', m => {if(m.type()==='error') row.consoleErrors.push(m.text());});
        page.on('pageerror', e => row.pageErrors.push(e.stack || e.message));
        page.on('response', r => {if(r.status()>=400 && r.url().startsWith('http://127.0.0.1:8000')) row.failedRequests.push({url:r.url(),status:r.status()});});
        // External services are not exercised with fixture data.
        await page.route('**/*', route => { if(route.request().url().startsWith('http://127.0.0.1:8000')) return route.continue(); row.blockedExternalRequests.push(route.request().url()); return route.abort(); });
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
          assert.equal(await student.locator('select[name="marks[1][attendance]"]').inputValue(),'1');
          assert.equal(await student.locator('select[name="marks[1][homework]"]').inputValue(),'1');
          const submission = page.waitForRequest(r => r.method()==='POST' && r.url().includes('/checklists/') && r.url().includes('/students/3'));
          await student.getByRole('button',{name:"Save this student's eight sessions"}).click();
          const submitted = new URLSearchParams((await submission).postData());
          row.submittedAttendance = submitted.get('marks[1][attendance]');
          row.submittedHomework = submitted.get('marks[1][homework]');
          assert.equal(row.submittedAttendance,'1'); assert.equal(row.submittedHomework,'1');
          await page.getByText('Eight-session attendance and homework saved.',{exact:false}).waitFor();
        } else if(role !== 'Admin') {
          await page.getByText('Attendance: 1/8',{exact:false}).first().waitFor();
          await page.getByText('Homework completed: 1/8',{exact:false}).first().waitFor();
          if(role==='Parent') {
            assert.ok(await page.getByRole('link',{name:'Recorded sessions',exact:false}).count()>=2);
          }
        }
        if(role==='Admin') {
          await page.getByRole('link',{name:'Students & progress',exact:true}).last().click();
          await page.waitForURL('**/learning/academy');
          assert.equal(await page.getByRole('heading',{name:'Admin dashboard',exact:true}).count(),0);
        }
        await page.screenshot({path:`tests/browser/artifacts/${role.toLowerCase()}-${width}.png`,fullPage:true});
        await page.goto('http://127.0.0.1:8000/learning/academy/notices');
        await page.getByRole('heading',{name:'Notice board',exact:true}).waitFor();
        await page.locator('input[name="q"]').fill('إعلان');
        await page.getByRole('button',{name:'Search',exact:true}).click();
        await page.getByRole('heading',{name:'إعلان الأكاديمية',exact:true}).waitFor();
        await page.getByText('Literal {{ 7 * 7 }} remains text',{exact:true}).waitFor();
        // Exercise the actual sidebar hit area, including padding, rather than a direct URL.
        if(width<768) await page.locator('button[onclick*="showsidebar"]').first().click();
        const menu=page.locator(width<768 ? '#res_sidebar' : '.sidebar .header-wrapper-b');
        await menu.getByRole('link',{name:'Dashboard',exact:true}).first().click({position:{x:8,y:8}});
        await page.waitForURL('**/learning/academy/dashboard');
        await page.getByRole('heading',{name:`${role} dashboard`,exact:true}).waitFor();
        row.dashboardClick='pass';
        const wrong=await context.request.get('http://127.0.0.1:8000/learning/academy/not-a-real-page');
        assert.equal(wrong.status(),404);
        assert.equal(row.pageErrors.length,0);
        assert.equal(row.failedRequests.length,0);
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
