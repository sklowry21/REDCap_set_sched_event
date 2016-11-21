# REDCap_set_sched_event
This is a REDCap plugin that finds the date (and time if available) of scheduled events for a project or 
for a record in a project and sets the value of a datetime field with that information.

You must pass in the pid and the variable name of the field that is to be filled with the event date (and time, if available).

You can either add a form that has just a datetime field to hold the datetime of the event and add that form 
to all of the events for which you want the datetime loaded or just add the field to an existing form that is all
of the events for which you want the datetime loaded.

If you use a project bookmark to run this plugin, you should check the checkbox that says to pass the project ID and 
also include a parameter for the field.  You can also check the checkbox to pass the record ID.  If you do that, then
when a user is on a form for a particular record, it will only update the field for that record, not all records
in the project.  For example: https://your.redcap.url/plugins/set_sched_event.php?field="event_datetime"
