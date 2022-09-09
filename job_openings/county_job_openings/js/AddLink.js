window.addEventListener('load', function() {
  var targetUl = document.getElementById('block-seven-content').getElementsByClassName('admin-list')[0];

  var li = document.createElement('li');
  li.classList.add('clearfix');

  var a = this.document.createElement('a');
  a.setAttribute('href', 'add/county_job_openings_templated');

  var span = this.document.createElement('span');
  span.classList.add('label');
  span.appendChild(document.createTextNode('Create County Job from Template'));

  var div = this.document.createElement('div');
  div.classList.add('description');
  div.appendChild(document.createTextNode('Select a template to create a County Job Opening'));

  a.appendChild(span);
  a.appendChild(div);
  li.appendChild(a);

  targetUl.prepend(li);
});
