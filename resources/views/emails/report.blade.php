<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$subject}}</title>
</head>
@php
if($remaining < 0){
    $remainingColor = 'text-red-500';
}elseif($budget_percentage_used >= 80){
    $remainingColor = 'text-red-600';
}elseif($budget_percentage_used >= 60){
    $remainingColor = 'text-orange-400';
}else{
    $remainingColor = 'text-black';
}
@endphp
<body style="font-family: Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding-top: 16px;">
    <tr>
        <td align="center">{{__('report.Yourreportisready')}}
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #e2e8f0; border-radius: 8px; background-color: #ffffff; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); padding: 16px;">
                <tr>
                    <td style="padding: 16px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding-bottom: 16px;text-align: center;">
                                  <div style="font-size: 16px; color: #9ca3af;">{{__('report.Remaining')}}</div>
                                  <div style="font-size: 32px; font-weight: bold; color: {{$remainingColor}};">{{$remaining}}</div>
                                  <div style="font-size: 14px; color: #6b7280;">{{__('report.Budget')}} {{number_format($budget->attributes->auto_budget_amount,0)}}</div>
                              </td>
                              <td style="padding-bottom: 16px;text-align: center;">
                                <div style="font-size: 16px; color: #9ca3af;">{{__('report.Spent')}}</div>
                                <div style="font-size: 32px; font-weight: bold; color: #ef4444;">{{$spentSum}}</div>
                                <div style="font-size: 14px; color: #6b7280;">{{$budget_percentage_used}}%</div>
                            </td>

                            <td style="padding-bottom: 16px;text-align: center;">
                              <div style="font-size: 16px; color: #9ca3af;">{{$tillText}}</div>
                              <div style="font-size: 32px; font-weight: bold;">{{number_format($remainingDays,0)}}</div>
                              <div style="font-size: 14px; color: #ffffff;">{{__('report.days')}}</div>
                          </td>
                          </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding-top: 16px;">
            <a href="{{ url('/budgets/' . $budget->id) }}" style="font-size: 14px; color: #3b82f6; text-decoration: none;">{{__('report.moreinfo')}}</a>
        </td>
    </tr>
</table>

</body>
</html>