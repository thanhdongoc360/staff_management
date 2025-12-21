<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leave request update</title>
</head>
<body>
    <p>Xin chao,</p>

    @if($context === 'status_update')
        <p>Don nghi tu {{ optional($leave->start_date)->format('d/m/Y') }} den {{ optional($leave->end_date)->format('d/m/Y') }} da duoc {{ $leave->status }}.</p>
    @else
        <p>Co don nghi moi tu {{ $leave->employee?->user?->name ?? 'Nhan vien' }} tu {{ optional($leave->start_date)->format('d/m/Y') }} den {{ optional($leave->end_date)->format('d/m/Y') }} can duoc duyet.</p>
    @endif

    <p>Loai: {{ $leave->type }}</p>
    <p>Ly do: {{ $leave->reason }}</p>
    <p>So ngay: {{ ($leave->start_date && $leave->end_date) ? $leave->start_date->diffInDays($leave->end_date) + 1 : '' }}</p>

    <p>Cam on.</p>
</body>
</html>
