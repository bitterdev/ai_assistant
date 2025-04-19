<?php

/** @var string $parentPagePath */
/** @var string $pageName */
/** @var string $pageSlug */
/** @var string $userName */
/** @var string $pageTypeHandle */
/** @var string $pageTemplateHandle */
/** @var string $locale */
/** @var string $contentDescription */

echo <<<PROMPT
You are an expert in Concrete CMS and your job is to generate a rich and impressive XML file in Concrete5 CIF 1.0 format.

This XML file will be used to create a full demo page for the Concrete CMS AI Assistant add-on. The content must be beautiful, well-structured, and full of diverse blocks to impress users and show the potential of the tool.

---

📥 INPUT:
- Page Title: "$pageName"
- Page Path: "$parentPagePath/$pageSlug"
- Created By: "$userName"
- Page Type Handle: "$pageTypeHandle"
- Page Template Handle: "$pageTemplateHandle"
- Locale: "$locale"
- Topic: "$contentDescription"

---

📌 REQUIREMENTS:
- Output **only a valid Concrete5 CIF 1.0 XML document**
- Include **at least 7–20 blocks**, such as:
  - `page_title`
  - `content`
  - `core_area_layout` with columns
  - `image`
  - `horizontal_rule`
  - `feature`
- Use a variety of Concrete block types to create a rich layout
- Content should include:
  - Well-formatted HTML (`<h2>`, `<ul>`, `<strong>`, etc.)
  - Several sections and paragraphs
  - Headings, bullet lists, and images
- Word count: **at least 800 words**
- Image references should use: `{ccm:export:file:placeholder.jpg}`
- No inline images always us image block type if you want to place an image
- Always use <fID>{ccm:export:file:placeholder.jpg}</fID> in image blocks other file names won't work

---

📄 XML STRUCTURE EXAMPLE (use as format reference):

<?xml version="1.0" encoding="UTF-8"?>
<concrete5-cif version="1.0">
    <pages>
        <page name="Test Page" path="/test-page" public-date="2025-04-19 18:21:10" filename="" pagetype="page"
              template="full" user="admin" description="" package="">
            <area name="Main">
                <blocks>
                    <block type="page_title" name="">
                        <data table="btPageTitle">
                            <record>
                                <useCustomTitle>1</useCustomTitle>
                                <titleText>Test Page</titleText>
                                <formatting>h1</formatting>
                            </record>
                        </data>
                    </block>
                    <block type="content" name="">
                        <data table="btContentLocal">
                            <record>
                                <content>
                                    <![CDATA[<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus.</p><p>Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vestibulum volutpat pretium libero. Cras id dui. Aenean ut</p>]]>
                                </content>
                            </record>
                        </data>
                    </block>
                    <block type="core_area_layout" name="">
                        <arealayout type="theme-grid" columns="12">
                            <columns>
                                <column span="4" offset="0">
                                    <block type="feature" name="">
                                        <data table="btFeature">
                                            <record>
                                                <icon>fas fa-bell</icon>
                                                <title>
                                                    Lorem ipsum
                                                </title>
                                                <paragraph>
                                                    <![CDATA[<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis</p>]]>
                                                </paragraph>
                                                <titleFormat>h4</titleFormat>
                                            </record>
                                        </data>
                                    </block>
                                </column>
                                <column span="4" offset="0">
                                    <block type="feature" name="">
                                        <data table="btFeature">
                                            <record>
                                                <icon>fas fa-bell</icon>
                                                <title>
                                                    Lorem ipsum
                                                </title>
                                                <paragraph>
                                                    <![CDATA[<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis</p>]]>
                                                </paragraph>
                                                <titleFormat>h4</titleFormat>
                                            </record>
                                        </data>
                                    </block>
                                </column>
                                <column span="4" offset="0">
                                    <block type="feature" name="">
                                        <data table="btFeature">
                                            <record>
                                                <icon>fas fa-bell</icon>
                                                <title>
                                                    Lorem ipsum
                                                </title>
                                                <paragraph>
                                                    <![CDATA[<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis</p>]]>
                                                </paragraph>
                                                <titleFormat>h4</titleFormat>
                                            </record>
                                        </data>
                                    </block>
                                </column>
                            </columns>
                        </arealayout>
                    </block>
                    <block type="horizontal_rule" name=""/>
                    <block type="core_area_layout" name="">
                        <arealayout type="theme-grid" columns="12">
                            <columns>
                                <column span="4" offset="0">
                                    <block type="image" name="">
                                        <data table="btContentImage">
                                            <record>
                                                <fID>{ccm:export:file:thumbnail.jpg}</fID>
                                                <sizingOption>thumbnails_default</sizingOption>
                                            </record>
                                        </data>
                                    </block>
                                </column>
                                <column span="8" offset="0">
                                    <block type="content" name="">
                                        <data table="btContentLocal">
                                            <record>
                                                <content>
                                                    <![CDATA[<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac,</p>]]>
                                                </content>
                                            </record>
                                        </data>
                                    </block>
                                </column>
                            </columns>
                        </arealayout>
                    </block>
                    <block type="content" name="">
                        <data table="btContentLocal">
                            <record>
                                <content>
                                    <![CDATA[<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu.</p><p>In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ipsum. Nam quam nunc, blandit vel, luctus pulvinar,</p>]]>
                                </content>
                            </record>
                        </data>
                    </block>
                </blocks>
            </area>
        </page>
    </pages>
</concrete5-cif>

---

❗ DO NOT include this prompt or instructions in your output.
ONLY return a valid Concrete5 CIF XML file that matches the format and contains rich content.

PROMPT;
