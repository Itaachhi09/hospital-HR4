// Node-based smoke test for enrollment endpoints (employee)
// Requires node and node-fetch installed. This script assumes you have a valid session cookie saved in cookie_employee.txt

const fetch = require('node-fetch');
const fs = require('fs');
const COOKIE = fs.existsSync('cookie_employee.txt') ? fs.readFileSync('cookie_employee.txt','utf8').trim() : '';
const BASE = 'http://localhost/php/api/';

(async ()=>{
  try{
    // Attempt to create enrollment (employee - allowed but will be forced to their employee_id in session)
    let res = await fetch(BASE+'hmo_enrollments.php', {
      method: 'POST', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ employee_id: 9999, plan_id: 1, start_date: '2025-10-01', end_date: '2026-09-30', status: 'Active' })
    });
    let j = await res.json(); console.log('create-as-employee', j);
    const id = j.id;
    if (!id) return;

    // Update enrollment (employee owns it)
    res = await fetch(BASE+`hmo_enrollments.php?id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ plan_id:1, start_date:'2025-10-01', end_date:'2026-09-30', status:'Active' }) }); j = await res.json(); console.log('update-as-employee', j);

    // Terminate (employee)
    res = await fetch(BASE+`hmo_enrollments.php?id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ status: 'Terminated', end_date: '2025-10-04' }) }); j = await res.json(); console.log('terminate-as-employee', j);

    // Attempt delete (should be forbidden for non-admin)
    res = await fetch(BASE+`hmo_enrollments.php?id=${id}`, { method: 'DELETE', headers: {'Cookie': COOKIE} }); j = await res.json(); console.log('delete-as-employee', j);
  }catch(e){ console.error(e); }
})();
