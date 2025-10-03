// Node-based smoke test for enrollment endpoints (admin)
// Requires node and node-fetch installed. This script assumes you have a valid session cookie saved in cookie.txt

const fetch = require('node-fetch');
const fs = require('fs');
const COOKIE = fs.existsSync('cookie.txt') ? fs.readFileSync('cookie.txt','utf8').trim() : '';
const BASE = 'http://localhost/php/api/';

(async ()=>{
  try{
    // Create enrollment
    let res = await fetch(BASE+'hmo_enrollments.php', {
      method: 'POST', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ employee_id: 1, plan_id: 1, start_date: '2025-10-01', end_date: '2026-09-30', status: 'Active' })
    });
    let j = await res.json(); console.log('create', j);
    const id = j.id;
    if (!id) return;

    // Update enrollment
    res = await fetch(BASE+`hmo_enrollments.php?id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ plan_id:1, start_date:'2025-10-01', end_date:'2026-09-30', status:'Active' }) }); j = await res.json(); console.log('update', j);

    // Terminate enrollment
    res = await fetch(BASE+`hmo_enrollments.php?id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ status: 'Terminated', end_date: '2025-10-04' }) }); j = await res.json(); console.log('terminate', j);

    // Delete enrollment
    res = await fetch(BASE+`hmo_enrollments.php?id=${id}`, { method: 'DELETE', headers: {'Cookie': COOKIE} }); j = await res.json(); console.log('delete', j);
  }catch(e){ console.error(e); }
})();
