This folder is for the data points from MyData, with one file for each year.

In 2026, we ran into an issue where Microsoft Excel will put a hidden character
at the beginning of the first line when saving a CSV file. We have code that
should bypass this hidden character.

To clean up the file saved from Excel, do the following:
1) Move the file to a temporary file name
2) cat temp-file-name
3) Highlight and copy the output
4) vi the real file, and paste in the data
5) Delete the temp file

Might be able to:
1) vi filename
2) move to the right a couple spaces
3) d0
4) Add in characters so the first word is County
