<li>
	<% if(children.length > 0) { %>
		<a href="#" class="has-children <%= _.escape(classes.join(' ')) %>" title="<%= _.escape(attr_title) %>"><%= title %></a>
		<div class="mp-level">
			<div class="mp-level-header">
				<h2><%= title %></h2>
				<a class="mp-back" href="#"><%= WpvPushMenu.back %></a>
			</div>
			<ul>
				<% if(! (/^\s*$/.test(url)) ) { %>
					<li><a href="<%= _.escape(url) %>" class="<%= _.escape(classes.join(' ')) %>" title="<%= _.escape(attr_title) %>"><%= title %></a></li>
				<% } %>
				<%= content %>
			</ul>
		</div>
	<% } else { %>
		<a href="<%= _.escape(url) %>" class="<%= _.escape(classes.join(' ')) %>" title="<%= _.escape(attr_title) %>"><%= title %></a>
	<% } %>
</li>