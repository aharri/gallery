<?xml version="1.0" encoding="utf-8"?>
<!--
 * $Id: site.xsl,v 1.8 2007/10/05 20:47:34 iku Exp $
 *
 * Copyright (c) 2007 Antti Harri <iku@openbsd.fi>
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
</xsl:template>

<xsl:template name="viewimage">
				<div id="viewimage">
					<img src="{/root/showimage/image}" alt="{/root/showimage/description}"/>
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
							<xsl:value-of select="time"/>
						</span>
					</div> <!-- directory box -->
				</xsl:for-each>
			</div> <!-- directories -->
			<hr/>
		</xsl:if>
</xsl:template>

<xsl:template name="pagination">
		<xsl:if test="/root/paging">
			<ul id="paging">
				<li>&lt;</li>
				<xsl:for-each select="/root/paging">
					<li>
						<xsl:choose>
							<xsl:when test="link">
								<a href="?{link}">
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
		<hr/>
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
