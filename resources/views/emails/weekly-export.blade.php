<x-mail::message>
# Weekly Access Log Export

The authorized access logs for the past week have been successfully exported.

Click the button below to view and download the report in your browser.

<x-mail::button :url="$downloadUrl" color="success">
View Report
</x-mail::button>

Thank you,<br>
{{ config('app.name') }}
</x-mail::message>
