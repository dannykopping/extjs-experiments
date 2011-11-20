package org.aerialframework.libs.as3crypto.util.asn1.parser
{
    import org.aerialframework.libs.as3crypto.util.asn1.type.ASN1Type;

    public function optional(o:ASN1Type):ASN1Type
    {
        o = o.clone();
        o.optional = true;
        return o;
    }
}