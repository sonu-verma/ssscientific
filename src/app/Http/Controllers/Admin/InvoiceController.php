<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Invoice;
use App\Models\Admin\Quote;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
   public function index(){
       $invoices = Invoice::with('quote')->with('purchaseOrder')->get()->all();
        return view('admin.invoices.index',[
            'invoices' => $invoices
        ]);
   }
   public function create(Request $request){
        return view('admin.invoices.create');
   }

   public function store(Request $request){

       $request->validate([
           'quote_id' => 'required',
           'po_id' => 'required'
       ]);

       $invoice = new Invoice();
       $invoice->quote_id =  $request->get('quote_id');
       $invoice->invoice_no =  Invoice::invoiceNumber();
       $invoice->po_no =  $request->get('po_id');
       $invoice->gst_no =  $request->get('gst_no');
       $invoice->freight =  $request->get('freight');
       $invoice->status =  $request->get('status');
       $invoice->save();

       return redirect()->route('invoices')->with("invoiceSuccessMsg",'Invoice create successfully.');
   }

   public function edit(Request $request,$invoice_id){
       $invoice = Invoice::where('id', $invoice_id)->with('quote')->with('purchaseOrder')->get()->first();
       if($invoice){
           return view('admin.invoices.edit',[
               'model' => $invoice
           ]);
       }
   }

   public function update(Request $request){
       $invoice_id = $request->get('invoice_id');
       $invoice = Invoice::where('id', $invoice_id)->with('quote')->with('purchaseOrder')->get()->first();
       if($invoice){

           $invoice->quote_id =  $request->get('quote_id');
           $invoice->po_no =  $request->get('po_id');
           $invoice->gst_no =  $request->get('gst_no');
           $invoice->freight =  $request->get('freight');
           $invoice->status =  $request->get('status');
           $invoice->save();

           return redirect()->route('invoices')->with("invoiceSuccessMsg",'Invoice updated successfully.');
       }
   }

   public function destroy(Request $request,Invoice $invoice){
        if($invoice){
            $invoice->delete();
            return redirect()->back()->with('invoiceSuccessMsg','Invoice Deleted successfully.');
        }
   }

    public function downloadInvoice(Request $request,$invoice_id){
        $type = $request->get('type');
        $layout = true;
        if ($type == 'html') {
            $layout = false;
        }

        $invoice = Invoice::where('id', $invoice_id)->with('quote')->with('purchaseOrder')->get()->first();
        $var = [
            'title' => 'Testing Page Number In Body',
            'layout' => $layout,
            'invoice' => $invoice,
            'model' => $invoice->quote,
        ];
        $page = 'admin.pdf.invoice';
        $prefix='Invoice';

        if($type == 'pdf'){
            $pdf = \App::make('dompdf.wrapper');
            $pdf->getDomPDF()->set_option("enable_php", true);
            $pdf->loadView($page, $var);
            return $pdf->download(strtoupper($prefix).'-'.time().'-' . $invoice->invoice_no . '.pdf');

        }else{
            return view($page, $var);
        }
    }

}
