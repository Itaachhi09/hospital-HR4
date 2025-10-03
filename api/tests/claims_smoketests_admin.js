// Node-based smoke test for HMO Claims (admin)
// Requires node and node-fetch installed. This script assumes you have a valid session cookie saved in cookie.txt

const fetch = require('node-fetch');
const fs = require('fs');
const COOKIE = fs.existsSync('cookie.txt') ? fs.readFileSync('cookie.txt','utf8').trim() : '';
const BASE = 'http://localhost/php/api/';

(async ()=>{
  try{
    // File a claim (use an existing enrollment id)
    let res = await fetch(BASE+'hmo_claims.php', {
      method: 'POST', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ enrollment_id: 1, claim_date: '2025-10-04', hospital_clinic: 'ACME Hospital', diagnosis: 'Flu', claim_amount: 1500.00, remarks: 'ER visit' })
    });
    let j = await res.json(); console.log('file', j);
    const id = j.id; if (!id) return;

    // Update claim
    res = await fetch(BASE+`hmo_claims.php?id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ hospital_clinic: 'Updated Hospital', diagnosis: 'Updated diagnosis', claim_amount: 1700.00, remarks: 'Updated by admin' }) }); j = await res.json(); console.log('update', j);

    // Approve claim
    res = await fetch(BASE+`hmo_claims.php?id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ claim_status: 'Approved', remarks: 'Approved by admin' }) }); j = await res.json(); console.log('approve', j);

    // Delete claim
    res = await fetch(BASE+`hmo_claims.php?id=${id}`, { method: 'DELETE', headers: {'Cookie': COOKIE} }); j = await res.json(); console.log('delete', j);
  }catch(e){ console.error(e); }
})();
