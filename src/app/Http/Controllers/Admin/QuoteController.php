<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->ajax()){
           return  $this->actionAjaxIndex($request);
        }
        return view('admin.quotes.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.quotes.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $formType = $request->get('formType');
        $quoteStatus =  Quote::QUOTE_DRAFT;
        if(!array_key_exists('isDraft',$request->all())){
            $quoteStatus = Quote::QUOTE_REQUESTED;
//            $validator = $this->validateDetails($request);
//            if ($validator->fails()) {
//                return response()->json([
//                    'statusCode' => 400,
//                    'errors' => $validator->errors()
//                ]);
//            }

        }
        if(array_key_exists('quote_no',$request->all())){
            $quotes = Quote::where('quote_no',$request->input('quote_no'))->get()->first();
        }else{
            $quotes = new Quote();
            $quotes->created_at = time();
        }

        $customerEmail = $request->input('email');
        $quotes->cust_id = $request->input('id_user');
        $quotes->phone_number = $request->input('phone_number');
        $quotes->email = $request->input('email');
        $quotes->address = $request->input('address');
        $quotes->apt_no = $request->input('apt_no');
        $quotes->zipcode = $request->input('zipcode');
        $quotes->city = $request->input('city');
        $quotes->state = $request->input('state');
        $quotes->billing_option = (array_key_exists('billingChk', $request->all()))?1:0;
        $quotes->billing_address = $request->input('billing_address');
        $quotes->billing_apt_no = $request->input('billing_apt_no');
        $quotes->billing_zipcode = $request->input('billing_zipcode');
        $quotes->billing_city = $request->input('billing_city');
        $quotes->billing_state = $request->input('billing_state');
        $quotes->relation = $request->input('relation');
        $quotes->reference_from = $request->input('reference_from');
        $quotes->referral = $request->input('referral');
        $quotes->referral_agency = $request->input('referral_agency');
        $quotes->is_enquired = $request->input('is_enquired');
        $quotes->notes = $request->input('notes');
        $quotes->status = $quoteStatus;
        $quotes->created_by = Auth::user()->id;
        $quotes->save();
        if($quotes->wasRecentlyCreated){
            $quoteNo = generateQuoteNo($quotes->id);
            $quotes->quote_no = $quoteNo;
            $quotes->save();
            return response()->json([
                "status" => 200,
                "quoteId" => $quotes->id,
                "message" => "Proposal saved successfully."
            ]);
        }else{
            if($formType == 'saveNext'){
                $result = $this->actionCreateQuote($request,$quotes->id);
//                return redirect(route('prhsOrder.create', ['token' => $result['token'],'quote_no' => $result['quote_no']]));
                return response()->json([
                    "status" => 200,
                    "quote_no" => $result['quote_no'],
                    "token" => $result['token'],
                    "message" => "Proposal updated successfully"
                ]);
            }else{
                return response()->json([
                    "status" => 200,
                    "quoteId" => $quotes->id,
                    "message" => "Proposal updated successfully"
                ]);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Quote $quote)
    {
        return view('admin.quotes.edit',[
            'model' => $quote
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Quote $quote)
    {
        $customerEmail = $request->input('email');
        $quote->cust_id = $request->input('id_user');
        $quote->phone_number = $request->input('phone_number');
        $quote->email = $request->input('email');
        $quote->address = $request->input('address');
        $quote->apt_no = $request->input('apt_no');
        $quote->zipcode = $request->input('zipcode');
        $quote->city = $request->input('city');
        $quote->state = $request->input('state');
        $quote->billing_option = (array_key_exists('billingChk', $request->all()))?1:0;
        $quote->billing_address = $request->input('billing_address');
        $quote->billing_apt_no = $request->input('billing_apt_no');
        $quote->billing_zipcode = $request->input('billing_zipcode');
        $quote->billing_city = $request->input('billing_city');
        $quote->billing_state = $request->input('billing_state');
        $quote->relation = $request->input('relation');
        $quote->reference_from = $request->input('reference_from');
        $quote->referral = $request->input('referral');
        $quote->referral_agency = $request->input('referral_agency');
        $quote->is_enquired = $request->input('is_enquired');
        $quote->notes = $request->input('notes');
        $quote->created_by = Auth::user()->id;
        $quote->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    /**
     * TO load data by ajax in datatable
     */

    public function actionAjaxIndex(Request $request){
        $columns = ['id', 'cust', 'status', 'quote_no', 'created_at'];

        $sEcho = $request->get('sEcho', 1);

        $start = $request->get('iDisplayStart', 0);
        $limit = $request->get('iDisplayLength', 0);

        $quote_status = $request->get('quote_status', 'All');

        $colSort = $columns[(int) $request->get('iSortCol_0', 2)];
        $colSortOrder = strtoupper($request->get('sSortDir_0', 'desc'));
        $searchTerm = trim($request->get('sSearch', ''));

        $page = ($start / $limit) + 1;
        if ($page < 1) {
            $page = 1;
        }

        $currentPage = $page;
        Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });

        $tblQuote = Quote::getTableName();
        $tblUser = 'users';

        $selectColumns = [
            $tblQuote . '.id',
            $tblQuote . '.reference',
            $tblQuote . '.notes',
            $tblQuote . '.status',
            $tblQuote . '.quote_no',
            $tblQuote . '.token',
            $tblQuote . '.phone_number',
            $tblQuote . '.address',
            $tblQuote . '.apt_no',
            $tblQuote . '.zipcode',
            $tblQuote . '.city',
            $tblQuote . '.created_at',
            $tblQuote . '.property_address',
            $tblUser . '.id as id_user',
            $tblUser . '.name',
//            $tblUser . '.last_name',
        ];
        $source = Quote::select($selectColumns)
            ->leftJoin($tblUser, $tblUser . '.id', '=', $tblQuote . '.cust_id');
//            ->whereNotIn($tblQuote.'.status', [Quote::QUOTE_TEST]);

        if ($searchTerm !== '' && strlen($searchTerm) > 0) {
            $source->where(function ($query) use ($searchTerm, $tblQuote, $tblUser) {
//                $query->where($tblQuote . '.reference', 'LIKE', '%' . $searchTerm . '%')
                $query->where($tblQuote . '.quote_no', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere($tblQuote . '.token', 'LIKE', '%' . $searchTerm . '%')
//                    ->orWhere($tblQuote . '.reference', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere($tblQuote . '.address', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere($tblQuote . '.apt_no', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere($tblQuote . '.zipcode', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere($tblQuote . '.city', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere($tblUser . '.email', 'LIKE', '%' . $searchTerm . '%')
//                    ->orWhere($tblUser . '.first_name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere($tblUser . '.name', 'LIKE', '%' . $searchTerm . '%');
            });
        }
        if($quote_status != 'All'){
            $source->where($tblQuote.'.status',$quote_status);
//            $source->whereIn($tblQuote.'.status', [Quote::QUOTE_CREATED,Quote::PROPOSAL_CREATED,Quote::PROPOSAL_SENT,Quote::PROPOSAL_APPROVED,Quote::ORDER_PLACED,Quote::QUOTE_REQUESTED]);
//            $source->whereNotIn($tblQuote.'.status', [Quote::QUOTE_TEST]);
        }

        if($request->get('quote_type') == 'false'){
            $source->whereNotIn($tblQuote.'.status', [Quote::QUOTE_TEST]);
        }

//        $query = vsprintf(str_replace(array('?'), array('\'%s\''), $source->toSql()), $source->getBindings());
//        dd($query);

        switch ($colSort) {
            default:
                $source->orderBy($colSort, $colSortOrder);
                break;
        }

        $quotes = $source->paginate($limit, $columns);
        $aaData = [];
        foreach ($quotes as $key => $property) {
            $isProductPlaced = null;
            $href = '';

            $email_conversation = ' <a href="javascript:void(0);"><i data-toggle="tooltip" title="" class="fa fa-envelope-o" data-original-title="Email Conversations"></i></a>';
            $order_reference = '';
            $download_proposal = '';
            if($property->status > Quote::PROPOSAL_CREATED){
                $download_proposal = '<br> <a href="'.route('proposal.downloadProposa',['token' => $property->token,'type' => 'pdf','quoteNo'=>$property->quote_no]).'">Download Proposal</a>';
            }

            $client_name = $property->name;
//            .' '.$property->last_name;

            if($property->backOrder && $property->backOrder->signedProposal &&  $property->backOrder->signedProposal->signed_document_path && $property->status == 8){
                Quote::where('token',$property->token)->update(['status' => Quote::AGREEMENT_SIGNED]);
                $statusType = quote_status(Quote::AGREEMENT_SIGNED);
            }else{
                $statusType = quote_status($property->status);
            }

            $buttons = [
                'view' => [
                    'label' => 'View',
                    'attributes' => [
//                        'id' => $property->id.'_view',
                        'href' => $href,
                    ]
                ],
                'edit' => [
                    'label' => 'Edit',
                    'attributes' => [
                        'href' => route('quote.edit', ['quote' => $property->id]),
                    ]
                ]
            ];
            $aaData[] = [
                'id' => $property->id,
//                'id' => ++$key,
                'property_address' => $property->property_address,
                'quote_no' => $property->quote_no.$order_reference.$download_proposal,
                'cust_info' => '<a href="" target="_blank">'.$client_name.'</a><br>'.$property->email.'<br>'.$property->phone_number,
                'status' => $statusType,
                'created_at' => __datetime($property->created_at),
                'controls' =>  table_buttons($buttons, false)
            ];
        }

        $total = $quotes->total();
        $output = array(
            "sEcho" => $sEcho,
            "iTotalRecords" => $total,
            "iTotalDisplayRecords" => $total,
            "aaData" => $aaData
        );
        return response()->json($output);
    }

    public function downloadQuote(Request $request){
        dd($request->all());
    }
}