
            <table>
                <thead >
                <tr>
                    <td>م</td>
                    <td>التاريخ</td>
                    <td>صادر له</td>
                    <td>مدفوع منه</td>
                    <td>الرصيد</td>
                    <td>البيان</td>
                </tr>
                </thead>
                <tbody>
                
                <?php  $c = 1; ?>

                <?php $balance = 0; $show_type = '';  $data = '('; ?>
                @foreach($collection as $col)
                    {{-- {{ dd($col) }} --}}
                    @if(in_array($col->bill_id, $deleted_bills) && in_array($col->type, [34, 30, 31]) || ($col->bill_id & ! $col->saleProcess) || ($col->transaction_type == '....'))
                    @else
                        @if(! in_array($col->type, [34, 30, 31]) )
                            <?php
                            $balance = balance($col, $pre_balance);
                            if (in_array($col->type, [4, 8, 111]) ) {
                                $balance = $col->balance;
                            }
                            ?>
{{-- 
                            @if(request('details') && $col->has_bill && in_array($col->type, [0, 1]))
                                <tr>
                                    <td colspan="10">
                                        @include('clients::sale-bill-data', array('col' => $col, 'balance' => price_decimal($balance)))
                                    </td>
                                </tr>
                            @else --}}
                                @if(request('details'))
                                    <tr>
                                        <td>م</td>
                                        <td>التاريخ</td>
                                        <td>صادر له</td>
                                        <td>مدفوع منه</td>
                                        <td>الرصيد</td>
                                        <td>البيان</td>
                                    </tr>
                                @endif
                                <?php $data .= $col->id . ', '; ?>
                                <tr>
                                    <td>{{ $c }}</td>
                                    <td>{{$col->pay_day}}</td>
                                    <td>{{ $col->first_cell ? price_decimal($col->first_cell) : '' }}</td>
                                    <td>{{ $col->second_cell ? price_decimal($col->second_cell) : '' }}</td>
                                    <td>{{ price_decimal($balance) }}</td>
                                    <td>
                                        @if( $col->installment_id > 0 )
                                            سداد قسط
                                        @else
                                            {!! $col->transaction_type !!}
                                        @endif
                                    </td>
                                </tr>
                            {{-- @endif --}}
                            <?php
                            $pre_balance = $balance; $c++;

                            if (!in_array($col->type, [0, 1])) {
                                $show_type = 'hidden';
                            } else {
                                $show_type = '';
                            }

                            ?>

                        @endif
                    @endif
                @endforeach

                </tbody>
            </table>