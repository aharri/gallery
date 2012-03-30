<?xml version="1.0" encoding="utf-8"?>
<!--
 *
 * Copyright (c) 2007 Antti Harri <iku@openbsd.fi>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 *
-->

<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output 
	method="xml"
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" 
	indent="yes"/>

<xsl:template name="thumbnails">
	<xsl:if test="/root/thumbnails">
		<hr/>
		<div id="thumbnails">
			<xsl:for-each select="/root/thumbnails">
				<div>
					<a href="?{link}">
						<img src="{thumbnail}" alt="{link}"/>
					</a>
					<p>
						<xsl:value-of select="name"/>
					</p>
				</div>
			</xsl:for-each>
		</div> <!-- thumbnails -->
	</xsl:if>
</xsl:template>

<xsl:template name="viewimage">
	<hr/>
	<div id="viewimage">
		<img src="{/root/showimage/image}" alt="{/root/showimage/description}"/>
		<br/>
		<span><xsl:value-of select="/root/showimage/description"/></span>
	</div> <!-- viewimage -->
</xsl:template>

<xsl:template name="login">
	<xsl:if test="/root/info/loginfailed">
		<p>Login failed.</p>
	</xsl:if>
	<form action="" method="post">
		<fieldset>
			<legend>Album needs authorization</legend>
			<p>
				<label for="user">User:</label><br/>
				<input id="user" type="text" name="user" />
			</p>
			<p>
				<label for="pass">Password:</label><br/>
				<input id="pass" type="password" name="pass" />
			</p>
			<input type="submit" name="datasubmitted" value="Login" />
		</fieldset>
	</form>
</xsl:template>

<xsl:template name="directories">
	<xsl:if test="/root/directories">
		<hr/>
		<div id="directories">
			<xsl:for-each select="/root/directories">
				<div>
					<xsl:if test="hilite">
						<xsl:attribute name="class">hilited</xsl:attribute>
					</xsl:if>
					<a href="?{link}">
						<xsl:value-of select="name"/>
					</a>
					<span>
						<xsl:if test="status='locked'">
							<img class="locked" src="layout/icon_locked.png" alt="[locked]"/>&#160;
						</xsl:if>
						<xsl:value-of select="time"/>
					</span>
				</div> <!-- directory box -->
			</xsl:for-each>
		</div> <!-- directories -->
	</xsl:if>
</xsl:template>

<xsl:template name="pagination">
	<xsl:if test="/root/paging">
		<hr/>
		<ul id="paging">
			<li>&lt;</li>
			<xsl:for-each select="/root/paging">
				<li>
					<xsl:choose>
						<xsl:when test="link">
							<a href="?{link}" id="{lid}">
								<xsl:value-of select="name"/>
							</a>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="name"/>
						</xsl:otherwise>
					</xsl:choose>
				</li>
			</xsl:for-each>
			<li>&gt;</li>
		</ul> <!-- paging -->
	</xsl:if>
</xsl:template>

<xsl:template match="/">
<html>
	<head>
		<title>
			<xsl:choose>
				<xsl:when test="/root/statusline/directories/name != ''">
					<xsl:for-each select="/root/statusline/directories">
						<xsl:if test="position() &gt; 1">
							<xsl:text> / </xsl:text>
						</xsl:if>
						<xsl:value-of select="name"/>
					</xsl:for-each>
				</xsl:when>
				<xsl:otherwise>
					Photo Gallery
					<xsl:value-of select="/root/program_version"/> by <xsl:value-of select="/root/author"/>
				</xsl:otherwise>
			</xsl:choose>
		</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="author" content="Antti Harri" />
		<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.3.1/build/reset/reset-min.css"/>
		<!--<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.3.0/build/base/base-min.css"/>-->
		<link type="text/css" rel="stylesheet" href="layout/style.css" />
		<script type="text/javascript" src="http://code.jquery.com/jquery-1.7.2.min.js"></script>
		<script type="text/javascript">
		$(document).ready(function(){
			$(document).keydown(function(ev){
				ev = ev||window.event;
				var $kk = ev.which?ev.which:ev.keyCode;
				var $prev = $("#link_previous").attr("href");
				var $next = $("#link_next").attr("href");
				if ($kk == 37) {
					$(window.location).attr('href', $prev);
					return false;
				} else if ($kk == 39) {
					$(window.location).attr('href', $next);
					return false;
				}
			});
		});
		</script>
	</head>

	<body>
		<!-- statusline -->
		<div id="statusline">
			Current album path: <a href="?/">root</a>
			<xsl:if test="/root/statusline/directories">
				<xsl:for-each select="/root/statusline/directories">
					/
					<a href="?{link}">
						<xsl:value-of select="name"/>
					</a>
				</xsl:for-each>
			</xsl:if>
		</div>
		<!-- statusline -->

		<!-- Perhaps this could be converted into ul list -->
		<xsl:call-template name="directories"/>

		<xsl:choose>
			<xsl:when test="/root/showlogin">
				<xsl:call-template name="login"/>
			</xsl:when>

			<xsl:otherwise>
				<xsl:call-template name="pagination"/>

				<xsl:choose>
					<xsl:when test="/root/showimage">
						<xsl:call-template name="viewimage"/>
					</xsl:when>
			
					<xsl:when test="/root/thumbnails">
						<xsl:call-template name="thumbnails"/>
					</xsl:when>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
		<hr/>
		<a href="https://github.com/aharri/gallery">Photo Gallery</a>
		<xsl:text> </xsl:text>
		<xsl:value-of select="/root/program_version"/> by <xsl:value-of select="/root/author"/>
	</body>
</html>
</xsl:template>

</xsl:stylesheet>
