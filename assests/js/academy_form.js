function calculateFees(){
    const admmissionfee = parseFloat(document.querySelector('[name= "admission_fee"]').value) || 0;
     const coachingfee = parseFloat(document.querySelector('[name= "coaching_fee"]').value) || 0;
    const totalfee = admmissionfee + coachingfee;
    const sgst = totalfee * 0.09;
    const cgst = totalfee * 0.09;
    const igst = totalfee * 0.18;
    const grandtotal = totalfee + sgst + cgst + igst ;

    document.querySelector('[name="total_fee"]').value = totalfee.toFixed(2);
    document.querySelector('[name="sgst"]').value = sgst.toFixed(2);
    document.querySelector('[name="cgst"]').value = cgst.toFixed(2);
    document.querySelector('[name="igst"]').value = igst.toFixed(2);
    document.querySelector('[name="grand_total"]').value = grandtotal.toFixed(2);
}

document.addEventListener("DOMContentLoaded",function(){
   document.querySelectorAll('[name = "admission_fee"] ,[name = "coaching_fee"]').forEach(input => {
    input.addEventListener('input',calculateFees);
   }) 
})
