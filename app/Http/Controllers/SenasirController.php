<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use Log;
use Datetime;
use Carbon\Carbon;
class SenasirController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // aumenta el tiempo máximo de ejecución de este script a 150 min: 
        ini_set ('max_execution_time', 36000); 
        // aumentar el tamaño de memoria permitido de este script: 
        ini_set ('memory_limit', '9600M');
        
        $excel = request('excel')??'';
        $order = request('order')??'';
        $pagination_rows = request('pagination_rows')??10;
        $conditions = [];

        $PresNumero = request('PresNumero')??'';
        $PresFechaDesembolso = request('PresFechaDesembolso')??'';
        $PadCedulaIdentidad = request('PadCedulaIdentidad')??'';
        $PadNombres = request('PadNombres')??'';
        $PadNombres2do = request('PadNombres2do')??'';
        $PadPaterno = request('PadPaterno')??'';
        $PadMaterno = request('PadMaterno')??'';
        $PadMatricula = request('PadMatricula')??'';

        if($PresNumero != '')
        {
            array_push($conditions,array('Prestamos.PresNumero','like',"%{$PresNumero}%"));
        }
        if($PresFechaDesembolso != '')
        {
            $date_from = Carbon::parse($PresFechaDesembolso);
            $date_to = Carbon::parse($PresFechaDesembolso);
            $date_to->hour = 23;
            $date_to->minute = 59;
            $date_to->second = 59;
            array_push($conditions,array('Prestamos.PresFechaDesembolso','<=',$date_to));
            array_push($conditions,array('Prestamos.PresFechaDesembolso','>=',$date_from));
        }

        if($PadMatricula != '')
        {
            array_push($conditions,array('Padron.PadMatricula','like',"%{$PadMatricula}%"));
        }
     
        if($PadCedulaIdentidad != '')
        {
            array_push($conditions,array('Padron.PadCedulaIdentidad','like',"%{$PadCedulaIdentidad}%"));
        }
        if($PadNombres != '')
        {
            array_push($conditions,array('Padron.PadNombres','like',"%{$PadNombres}%"));
        }
        if($PadNombres2do != '')
        {
            array_push($conditions,array('Padron.PadNombres2do','like',"%{$PadNombres2do}%"));
        }
        if($PadPaterno != '')
        {
            array_push($conditions,array('Padron.PadPaterno','like',"%{$PadPaterno}%"));
        }
        if($PadMaterno != '')
        {
            array_push($conditions,array('Padron.PadMaterno','like',"%{$PadMaterno}%"));
        }
        
        if($excel=='')//reporte excel hdp 
        {

            $loans =DB::table('Prestamos')->leftJoin('Padron','Padron.IdPadron','=','Prestamos.IdPadron')
                                            ->where($conditions)
                                            ->where('Prestamos.PresEstPtmo','=','V')
                                            ->where('Prestamos.PresSaldoAct','>',0)
                                            ->where('Padron.PadTipo','=','PASIVO')
                                            ->where('Padron.PadTipRentAFPSENASIR','=','SENASIR')
                                            ->whereExists(function ($query) {
                                                $query->select(DB::raw(1))
                                                      ->from('Amortizacion')
                                                      ->whereRaw("Amortizacion.IdPrestamo = Prestamos.IdPrestamo and Amortizacion.AmrSts != 'X'");
                                            })
                                            ->select('Prestamos.IdPrestamo','Prestamos.PresFechaDesembolso','Prestamos.PresNumero','Prestamos.PresCuotaMensual','Prestamos.PresSaldoAct','Prestamos.SolEntChqCod',
                                                    'Padron.IdPadron','Prestamos.Prestasaint')
                                            ->paginate($pagination_rows);

            //completando informacion de afiliado problemas de codificacion utf-8                                        
            $loans->getCollection()->transform(function ($item) {
                $padron = DB::table('Padron')->where('IdPadron',$item->IdPadron)->first();
                $item->PadTipo = utf8_encode(trim($padron->PadTipo));
                $item->PadNombres = utf8_encode(trim($padron->PadNombres));
                $item->PadNombres2do =utf8_encode(trim($padron->PadNombres2do));
                $item->PadPaterno =utf8_encode(trim($padron->PadPaterno));
                $item->PadMaterno =utf8_encode(trim($padron->PadMaterno));
                $item->PadCedulaIdentidad =utf8_encode(trim($padron->PadCedulaIdentidad));
                $item->PadExpCedula =utf8_encode(trim($padron->PadExpCedula));
                $item->PadMatricula =utf8_encode(trim($padron->PadMatricula));
                $item->PadMatriculaTit =utf8_encode(trim($padron->PadMatriculaTit));

                $departamento = DB::table('Departamento')->where('DepCod','=',$item->SolEntChqCod)->first();
                $item->Departamento = $departamento?$departamento->DepDsc:'';

                if($item->PresSaldoAct < $item->PresCuotaMensual)
                {
                    $item->Discount = $item->PresSaldoAct;
                }else
                {
                    $item->Discount = $item->PresCuotaMensual;
                }
                return $item;
            });
            return response()->json($loans->toArray());

        }else{

            $loans =DB::table('Prestamos')->leftJoin('Padron','Padron.IdPadron','=','Prestamos.IdPadron')
                                        ->where('Prestamos.PresEstPtmo','=','V')
                                        ->where('Prestamos.PresSaldoAct','>',0)
                                        ->where('Padron.PadTipo','=','PASIVO')
                                        ->where('Padron.PadTipRentAFPSENASIR','=','SENASIR')
                                        ->whereExists(function ($query) {
                                            $query->select(DB::raw(1))
                                                  ->from('Amortizacion')
                                                  ->whereRaw("Amortizacion.IdPrestamo = Prestamos.IdPrestamo and Amortizacion.AmrSts != 'X'");
                                        })
                                        ->select('Prestamos.IdPrestamo','Prestamos.PresFechaDesembolso','Prestamos.PresNumero','Prestamos.PresCuotaMensual','Prestamos.PresSaldoAct','Padron.PadTipo','Padron.PadCedulaIdentidad','Padron.PadNombres','Padron.PadNombres2do','Padron.IdPadron','Padron.PadMatricula','Prestamos.SolEntChqCod','Prestamos.Prestasaint')
                                    //    ->take(40)
                                        ->get();
    
            global $prestamos;
            $prestamos =[ array('FechaDesembolso','Numero','Tipo','MatriculaTitular','MatriculaDerechohabiente','CI','Extension','PrimerNombre','SegundoNombre','Paterno','Materno','SaldoActual','Cuota','Descuento','ciudad','Interes')];

            foreach($loans as $loan)
            {
            $padron = DB::table('Padron')->where('IdPadron','=',$loan->IdPadron)->first();

            // $loan->PresNumero = utf8_encode(trim($padron->PresNumero));
            $loan->PadNombres = utf8_encode(trim($padron->PadNombres));
            $loan->PadNombres2do =utf8_encode(trim($padron->PadNombres2do));
            $loan->PadPaterno =utf8_encode(trim($padron->PadPaterno));
            $loan->PadMaterno =utf8_encode(trim($padron->PadMaterno));
            $loan->PadMatriculaTit =utf8_encode(trim($padron->PadMatriculaTit));
            $loan->PadExpCedula =utf8_encode(trim($padron->PadExpCedula));

            $departamento = DB::table('Departamento')->where('DepCod','=',$loan->SolEntChqCod)->first();
            if($departamento)
            {

                $loan->City =$departamento->DepDsc; 
            }else{
                $loan->City = '';
            }
            
                   
            if($loan->PresSaldoAct < $loan->PresCuotaMensual)
            {
                $loan->Discount = $loan->PresSaldoAct;
            }else
            {
                $loan->Discount = $loan->PresCuotaMensual;
            }
            //Log::info(json_encode($padron));
            array_push($prestamos,array(
                    $loan->PresFechaDesembolso,
                    $loan->PresNumero,
                    $loan->PadTipo,
                    $loan->PadMatriculaTit,
                    $loan->PadMatricula,
                    $loan->PadCedulaIdentidad,
                    $loan->PadExpCedula,
                    $loan->PadNombres,
                    $loan->PadNombres2do,
                    $loan->PadPaterno,
                    $loan->PadMaterno,
                    $loan->PresSaldoAct,
                    $loan->PresCuotaMensual,
                    $loan->Discount,
                    $loan->City,
                    $loan->Prestasaint,
                ));
            }
            
            Excel::create('prestamos altas senasir',function($excel)
            {
                global $prestamos;
                
                        $excel->sheet('presamos vigentes',function($sheet) {
                                global $prestamos;
                                $sheet->fromModel($prestamos,null, 'A1', false, false);
                                $sheet->cells('A1:N1', function($cells) {
                                // manipulate the range of cells
                                $cells->setBackground('#058A37');
                                $cells->setFontColor('#ffffff');  
                                $cells->setFontWeight('bold');
                                });
                            });
                    
            })->download('xls');

        }
    }

    public function nuevos_senasir()
    {
         // aumenta el tiempo máximo de ejecución de este script a 150 min: 
         ini_set ('max_execution_time', 36000); 
         // aumentar el tamaño de memoria permitido de este script: 
         ini_set ('memory_limit', '9600M');
         
         $excel = request('excel')??'';
         $order = request('order')??'';
         $pagination_rows = request('pagination_rows')??10;
         $date = request('date')??'2018-08-31';
         $conditions = [];
 
         $PresNumero = request('PresNumero')??'';
         $PresFechaDesembolso = request('PresFechaDesembolso')??'';
         $PadCedulaIdentidad = request('PadCedulaIdentidad')??'';
         $PadNombres = request('PadNombres')??'';
         $PadNombres2do = request('PadNombres2do')??'';
         $PadPaterno = request('PadPaterno')??'';
         $PadMaterno = request('PadMaterno')??'';
         $PadMatricula = request('PadMatricula')??'';
 
         if($PresNumero != '')
         {
             array_push($conditions,array('Prestamos.PresNumero','like',"%{$PresNumero}%"));
         }
         if($PresFechaDesembolso != '')
         {
             $date_from = Carbon::parse($PresFechaDesembolso);
             $date_to = Carbon::parse($PresFechaDesembolso);
             $date_to->hour = 23;
             $date_to->minute = 59;
             $date_to->second = 59;
             array_push($conditions,array('Prestamos.PresFechaDesembolso','<=',$date_to));
             array_push($conditions,array('Prestamos.PresFechaDesembolso','>=',$date_from));
         }
 
         if($PadMatricula != '')
         {
             array_push($conditions,array('Padron.PadMatricula','like',"%{$PadMatricula}%"));
         }
      
         if($PadCedulaIdentidad != '')
         {
             array_push($conditions,array('Padron.PadCedulaIdentidad','like',"%{$PadCedulaIdentidad}%"));
         }
         if($PadNombres != '')
         {
             array_push($conditions,array('Padron.PadNombres','like',"%{$PadNombres}%"));
         }
         if($PadNombres2do != '')
         {
             array_push($conditions,array('Padron.PadNombres2do','like',"%{$PadNombres2do}%"));
         }
         if($PadPaterno != '')
         {
             array_push($conditions,array('Padron.PadPaterno','like',"%{$PadPaterno}%"));
         }
         if($PadMaterno != '')
         {
             array_push($conditions,array('Padron.PadMaterno','like',"%{$PadMaterno}%"));
         }
         
         if($excel=='')//reporte excel hdp 
         {
 
             $loans =DB::table('Prestamos')->leftJoin('Padron','Padron.IdPadron','=','Prestamos.IdPadron')
                                             ->join('PlanPagosPlan','PlanPagosPlan.IdPrestamo','=','Prestamos.IdPrestamo')
                                             ->where($conditions)
                                             ->where('Prestamos.PresEstPtmo','=','V')
                                             ->where('Prestamos.PresSaldoAct','>',0)
                                             ->where('Padron.PadTipo','=','PASIVO')
                                             ->where('Padron.PadTipRentAFPSENASIR','=','SENASIR')
                                             ->where('PlanPagosPlan.PlanFechaPago','=',$date)
                                             ->where('PlanPagosPlan.IdPlanNroCouta','=',1)
                                             ->whereNotExists(function ($query) {
                                                 $query->select(DB::raw(1))
                                                       ->from('Amortizacion')
                                                       ->whereRaw("Amortizacion.IdPrestamo = Prestamos.IdPrestamo and Amortizacion.AmrSts != 'X'");
                                             })
                                             ->select('Prestamos.IdPrestamo','Prestamos.PresFechaDesembolso','Prestamos.PresNumero','Prestamos.PresCuotaMensual','Prestamos.PresSaldoAct','Prestamos.SolEntChqCod',
                                                     'Padron.IdPadron',
                                                     'PlanPagosPlan.PlanCuotaMensual'
                                                     )
                                             ->paginate($pagination_rows);
 
             //completando informacion de afiliado problemas de codificacion utf-8                                        
             $loans->getCollection()->transform(function ($item) {
                 $padron = DB::table('Padron')->where('IdPadron',$item->IdPadron)->first();
                 $item->PadTipo = utf8_encode(trim($padron->PadTipo));
                 $item->PadNombres = utf8_encode(trim($padron->PadNombres));
                 $item->PadNombres2do =utf8_encode(trim($padron->PadNombres2do));
                 $item->PadPaterno =utf8_encode(trim($padron->PadPaterno));
                 $item->PadMaterno =utf8_encode(trim($padron->PadMaterno));
                 $item->PadCedulaIdentidad =utf8_encode(trim($padron->PadCedulaIdentidad));
                 $item->PadExpCedula =utf8_encode(trim($padron->PadExpCedula));
                 $item->PadMatricula =utf8_encode(trim($padron->PadMatricula));
                 $item->PadMatriculaTit =utf8_encode(trim($padron->PadMatriculaTit));
 
                 $departamento = DB::table('Departamento')->where('DepCod','=',$item->SolEntChqCod)->first();
                 $item->Departamento = $departamento?$departamento->DepDsc:'';
 
                 
                 $item->Discount = $item->PlanCuotaMensual;
                 
                 return $item;
             });
             return response()->json($loans->toArray());
 
         }else{
 
             $loans =DB::table('Prestamos')->leftJoin('Padron','Padron.IdPadron','=','Prestamos.IdPadron')
                                         ->join('PlanPagosPlan','PlanPagosPlan.IdPrestamo','=','Prestamos.IdPrestamo')
                                         ->where('Prestamos.PresEstPtmo','=','V')
                                         ->where('Prestamos.PresSaldoAct','>',0)
                                         ->where('Padron.PadTipo','=','PASIVO')
                                         ->where('Padron.PadTipRentAFPSENASIR','=','SENASIR')
                                         ->where('PlanPagosPlan.PlanFechaPago','=',$date)
                                         ->where('PlanPagosPlan.IdPlanNroCouta','=',1)
                                         ->whereNotExists(function ($query) {
                                             $query->select(DB::raw(1))
                                                   ->from('Amortizacion')
                                                   ->whereRaw("Amortizacion.IdPrestamo = Prestamos.IdPrestamo and Amortizacion.AmrSts != 'X'");
                                         })
                                         ->select('Prestamos.IdPrestamo','Prestamos.PresFechaDesembolso','Prestamos.PresNumero','Prestamos.PresCuotaMensual','Prestamos.PresSaldoAct','Padron.PadTipo','Padron.PadCedulaIdentidad','Padron.PadNombres','Padron.PadNombres2do','Padron.IdPadron','Padron.PadMatricula','Prestamos.SolEntChqCod','PlanPagosPlan.PlanCuotaMensual')
                                       //  ->take(40)
                                         ->get();
     
             global $prestamos;
             $prestamos =[ array('FechaDesembolso','Numero','Tipo','MatriculaTitular','MatriculaDerechohabiente','CI','Extension','PrimerNombre','SegundoNombre','Paterno','Materno','SaldoActual','Cuota','Descuento','ciudad')];
 
             foreach($loans as $loan)
             {
             $padron = DB::table('Padron')->where('IdPadron','=',$loan->IdPadron)->first();
 
             // $loan->PresNumero = utf8_encode(trim($padron->PresNumero));
             $loan->PadNombres = utf8_encode(trim($padron->PadNombres));
             $loan->PadNombres2do =utf8_encode(trim($padron->PadNombres2do));
             $loan->PadPaterno =utf8_encode(trim($padron->PadPaterno));
             $loan->PadMaterno =utf8_encode(trim($padron->PadMaterno));
             $loan->PadMatriculaTit =utf8_encode(trim($padron->PadMatriculaTit));
             $loan->PadExpCedula =utf8_encode(trim($padron->PadExpCedula));
 
             $departamento = DB::table('Departamento')->where('DepCod','=',$loan->SolEntChqCod)->first();
             if($departamento)
             {
 
                 $loan->City =$departamento->DepDsc; 
             }else{
                 $loan->City = '';
             }
            
             $loan->Discount = $loan->PlanCuotaMensual;
             //Log::info(json_encode($padron));
             array_push($prestamos,array(
                     $loan->PresFechaDesembolso,
                     $loan->PresNumero,
                     $loan->PadTipo,
                     $loan->PadMatriculaTit,
                     $loan->PadMatricula,
                     $loan->PadCedulaIdentidad,
                     $loan->PadExpCedula,
                     $loan->PadNombres,
                     $loan->PadNombres2do,
                     $loan->PadPaterno,
                     $loan->PadMaterno,
                     $loan->PresSaldoAct,
                     $loan->PresCuotaMensual,
                     $loan->Discount,
                     $loan->City,
                 ));
             }
             
            Excel::create('prestamos altas senasir',function($excel)
            {
                global $prestamos;
                
                        $excel->sheet('presamos vigentes',function($sheet) {
                                global $prestamos;
                                $sheet->fromModel($prestamos,null, 'A1', false, false);
                                $sheet->cells('A1:N1', function($cells) {
                                // manipulate the range of cells
                                $cells->setBackground('#058A37');
                                $cells->setFontColor('#ffffff');  
                                $cells->setFontWeight('bold');
                                });
                            });
                    
            })->download('xls');
 
         }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
}
